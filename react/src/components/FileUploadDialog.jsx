import { useRef, useState } from 'react';
import axiosClient from '../axios-client.js';
import { useStateContext } from '../contexts/ContextProvider.jsx';
import { styled } from '@mui/material/styles';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Grid from '@mui/material/Grid';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

const VisuallyHiddenInput = styled('input')({
    clip: 'rect(0 0 0 0)',
    clipPath: 'inset(50%)',
    height: 1,
    overflow: 'hidden',
    position: 'absolute',
    bottom: 0,
    left: 0,
    whiteSpace: 'nowrap',
    width: 1,
});

export default function FileUploadDialog({ isOpen, onClose }) {
    const fileDescriptionRef = useRef();

    const [errors, setErrors] = useState(null);
    const [selectedFile, setSelectedFile] = useState(null);
    const {setNotification} = useStateContext();

    const handleFileChange = (ev) => {
        const fileObj = ev.target.files[0];
        setSelectedFile(fileObj);
    }

    const handleClose = () => {
        setSelectedFile(null);
        setErrors('');
        onClose();
    }

    const handleFileUpload = () => {
        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('file_description', fileDescriptionRef.current.value);

        axiosClient.post('/file/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
            .then((response) => {
                if (response && response.status == 201) {
                    handleClose();
                    setNotification(response.data.message);
                }
            })
            .catch((err) => {
                const response = err.response;
                if (response && response.status == 422) {
                    setErrors(response.data.errors);
                    setTimeout(() => {
                        setErrors('');
                    }, 6000);
                }
            })
    }

    return (
        <Dialog open={isOpen} onClose={handleClose} fullWidth maxWidth="sm">
            <DialogTitle>
                Upload
            </DialogTitle>
            <DialogContent dividers>
                {errors && <Alert severity="error" sx={{ alignItems: 'center', }}>
                    {Object.keys(errors).map(key => (
                        <p key={key} style={{ margin: '5px' }}>{errors[key][0]}</p>
                    ))}
                </Alert>
                }
                <Grid sx={{ display: 'flex', }}>
                    <Button component="label" variant="contained" sx={{ my: 1, mr: 2, }}>
                        Choose file
                        <VisuallyHiddenInput type="file" onChange={handleFileChange} />
                    </Button>
                    {selectedFile && <Typography variant="body1" sx={{ py: 1.5, }}>
                        {selectedFile.name}
                    </Typography>
                    }
                </Grid>
                <TextField id="file_description" label="File Description" type="text" inputRef={fileDescriptionRef} variant="filled" margin="dense" fullWidth required />
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose}>Cancel</Button>
                <Button onClick={handleFileUpload}>Upload</Button>
            </DialogActions>
        </Dialog>
    )
}