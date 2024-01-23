// main.go
package main

import (
	"bytes"
	"encoding/base64"
	"encoding/gob"
	"encoding/json"
	"fmt"
	"net/http"
	"strconv"
	"strings"

	"github.com/fentec-project/gofe/abe"
)

var pubKey *abe.FAMEPubKey
var secKey *abe.FAMESecKey
var cipher *abe.FAMECipher

type EncryptRequest struct {
	AesKey    string `json:"aesKey"`
	UserID    []int  `json:"UserID"`
	PublicKey string `json:"ownerMPK"`
}

type DecryptRequest struct {
	UserID    int    `json:"userID"`
	Cipher    string `json:"cipher"`
	PublicKey string `json:"publicKey"`
	SecretKey string `json:"secretKey"`
}

func decryption(w http.ResponseWriter, r *http.Request) {
	// Decode JSON data from Laravel
	var requestData DecryptRequest
	err := json.NewDecoder(r.Body).Decode(&requestData)
	if err != nil {
		http.Error(w, "Error decoding JSON", http.StatusBadRequest)
		return
	}

	result, err := processDecryption(requestData)
	if err != nil {
		http.Error(w, "Error decrypting AES key", http.StatusInternalServerError)
		return
	}

	response := map[string]interface{}{
		"aes_key": string(result),
	}
	// Send the response back to Laravel
	jsonResponse, err := json.Marshal(response)
	if err != nil {
		http.Error(w, "Error encoding JSON", http.StatusInternalServerError)
		// return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	w.Write(jsonResponse)
}

func processDecryption(data DecryptRequest) (string, error) {
	fameInstance := abe.NewFAME()
	attributeString := []string{strconv.Itoa(data.UserID)}

	pubKeyByte, err := decodeToBytes(data.PublicKey)
	if err != nil {
		return "", err
	}
	buf := bytes.NewReader(pubKeyByte)
	dec := gob.NewDecoder(buf)
	if err := dec.Decode(&pubKey); err != nil {
		return "", err
	}

	secKeyByte, err := decodeToBytes(data.SecretKey)
	if err != nil {
		return "", err
	}
	buf = bytes.NewReader(secKeyByte)
	dec = gob.NewDecoder(buf)
	if err = dec.Decode(&secKey); err != nil {
		return "", err
	}

	cipherByte, err := decodeToBytes(data.Cipher)
	if err != nil {
		return "", err
	}
	buf = bytes.NewReader(cipherByte)
	dec = gob.NewDecoder(buf)
	if err = dec.Decode(&cipher); err != nil {
		return "", err
	}

	key, err := fameInstance.GenerateAttribKeys(attributeString, secKey)
	if err != nil {
		return "", err
	}
	decodedAESKey, err := fameInstance.Decrypt(cipher, key, pubKey)
	if err != nil {
		return "", err
	}
	fmt.Println("-----Decryption using ABE-----")
	fmt.Println("AES Key: ", decodedAESKey)
	return decodedAESKey, nil
}

func decodeToBytes(s string) ([]byte, error) {
	decodedData, err := base64.StdEncoding.DecodeString(s)
	if err != nil {
		return nil, err
	}

	return decodedData, nil
}

func encryption(w http.ResponseWriter, r *http.Request) {
	// Decode JSON data from Laravel
	var requestData EncryptRequest
	err := json.NewDecoder(r.Body).Decode(&requestData)
	if err != nil {
		http.Error(w, "Error decoding JSON", http.StatusBadRequest)
		return
	}

	result, err := processEncryption(requestData)
	if err != nil {
		http.Error(w, "Error generating ciphertext", http.StatusInternalServerError)
		return
	}

	var cipherBuffer bytes.Buffer
	encoder := gob.NewEncoder(&cipherBuffer)
	if err := encoder.Encode(result); err != nil {
		http.Error(w, "Error encoding byte array", http.StatusInternalServerError)
	}

	// Send the response back to Laravel
	jsonResponse, err := json.Marshal(cipherBuffer.Bytes())
	if err != nil {
		http.Error(w, "Error encoding JSON", http.StatusInternalServerError)
		return
	}

	fmt.Println("-----Encryption using ABE-----")
	fmt.Println("Ciphertext: ", result)
	fmt.Println(" ")

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	w.Write(jsonResponse)
}

func processEncryption(data EncryptRequest) (*abe.FAMECipher, error) {
	fameInstance := abe.NewFAME()

	var userIDStrings []string
	for _, userID := range data.UserID {
		userIDStrings = append(userIDStrings, strconv.Itoa(userID))
	}

	attributeString := strings.Join(userIDStrings, " OR ")

	msp, err := abe.BooleanToMSP(attributeString, false)
	if err != nil {
		return nil, err
	}

	pubKeyByte, err := base64.StdEncoding.DecodeString(data.PublicKey)
	if err != nil {
		return nil, err
	}

	// Decode the whole struct
	buf := bytes.NewReader(pubKeyByte)
	dec := gob.NewDecoder(buf)
	if err := dec.Decode(&pubKey); err != nil {
		return nil, err
	}

	cipher, err := fameInstance.Encrypt(data.AesKey, msp, pubKey)
	if err != nil {
		return nil, err
	}

	return cipher, nil
}

func generateMasterPublicSecretKey() ([]byte, []byte, error) {
	fameInstance := abe.NewFAME()
	publicKey, secretKey, err := fameInstance.GenerateMasterKeys()
	if err != nil {
		return nil, nil, err
	}

	// Convert the keys to byte arrays using gob encoding
	var publicKeyBuffer, secretKeyBuffer bytes.Buffer
	encoder := gob.NewEncoder(&publicKeyBuffer)
	if err := encoder.Encode(publicKey); err != nil {
		return nil, nil, err
	}

	encoder = gob.NewEncoder(&secretKeyBuffer)
	if err = encoder.Encode(secretKey); err != nil {
		return nil, nil, err
	}
	fmt.Println("-----Generating MPK & MSK------")
	fmt.Println("Master Public Key: ")
	fmt.Println(publicKey)
	fmt.Println(" ")
	fmt.Println("Master Secret Key: ")
	fmt.Println(secretKey)
	fmt.Println(" ")
	return publicKeyBuffer.Bytes(), secretKeyBuffer.Bytes(), nil
}

func sendMasterPublicSecretKey(w http.ResponseWriter, r *http.Request) {
	publicKeyBytes, secretKeyBytes, err := generateMasterPublicSecretKey()
	if err != nil {
		http.Error(w, "Error generating keys", http.StatusInternalServerError)
		return
	}

	keys := map[string]interface{}{
		"publicKey": publicKeyBytes,
		"secretKey": secretKeyBytes,
	}

	// Send the response back to Laravel
	jsonResponse, err := json.Marshal(keys)
	if err != nil {
		http.Error(w, "Error encoding JSON", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	w.Write(jsonResponse)
}

func main() {
	http.HandleFunc("/encrypt", encryption)
	http.HandleFunc("/decrypt", decryption)
	http.HandleFunc("/getKeys", sendMasterPublicSecretKey)
	http.ListenAndServe(":10000", nil)
}
