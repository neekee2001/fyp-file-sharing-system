## About Project
This project develops a web-based file-sharing platform using Laravel and React frameworks. This platform is built on top of the decentralized storage protocol, InterPlanetary File System (IPFS) to store the uploaded files. When files are uploaded, the contents will be encrypted using AES, and when files are shared, the AES key will be encrypted using the attribute-based encryption (ABE) method.

## Installation
Set up the local environment by ensuring the following services have been installed.

### MySQL Workbench 
MySQL Workbench is a graphical user interface tool used for developing, modifying, and managing MySQL databases. It supports both MySQL and MariaDB databases and offers features such as database modeling, query design, data migration, and performance analysis.

1. Install [MySQL Workbench](https://www.mysql.com/products/workbench/).
2. Launch MySQL Workbench and set up a new connection.

### IPFS
*IPFS (InterPlanetary File System) is a peer-to-peer distributed file system that allows for the secure and efficient storage and retrieval of files across a network of computers. 

1. Install [IPFS Kubo](https://docs.ipfs.tech/install/command-line/#install-official-binary-distributions).
2. Unzip the file to any file directory, such as C:/Program Files.
3. Navigate to the kubo folder with cd C:/Program Files/kubo command.
4. Check that Kubo is installed correctly with ipfs --version command.
5. Initialize IPFS node with the ipfs init command.
6. Start the IPFS daemon with the ipfs daemon command.

### Laravel
Before using Laravel, make sure that your local machine has PHP and Composer installed.
1. Install [PHP](https://www.php.net/downloads.php).
2. Install [composer](https://getcomposer.org/).

### React
1. Install [Node.js](https://nodejs.org/en).(Node.js comes with npm, so you don't need to install it separately.)
2. Check that Node.js and npm are installed correctly with node -v and npm -v command.

## Laravel and React Project Setup
To set up this project,
1. Clone this project with git clone command.
2. Open this project folder using any code editor. For example, Visual Studio Code.
3. Copy .env.example into .env. Configure database and IPFS credentials.
4. Start terminal/command prompt and run composer install.
5. Set the encryption key by executing php artisan key:generate.
6. Run migration php artisan migrate --seed.
7. Copy react/.env.example into react/.env. Adjust the VITE_API_BASE_URL parameter.
8. Open a new terminal/command prompt and navigate to the react folder with cd react command. Run npm install.

## Golang
Golang is used for attribute-based encryption (ABE).
1. Install [Go](https://go.dev/doc/install) version 1.21.5. This specific version is required to use the ABE library package.
2. Start terminal/command prompt and navigate to the ABE folder with cd ABE command.
3. Check that Go is installed correctly with go version command.
4. Run go get github.com/fentec-project/gofe/abe to install the required library.

## Run Project
To run this project, you should execute the following steps every time you open it.
1. Open this project folder using any code editor.
2. Start terminal/command prompt and navigate to the kubo folder cd C:/Program Files/kubo. Start the IPFS daemon by executing ipfs daemon command.
3. Start a new terminal/command prompt. Run php artisan serve command to start the Laravel backend server.
4. Start a new terminal/command prompt and navigate to the React folder with cd react command. Run npm run dev to start the vite server for React.
5. Start a new terminal/command prompt and run php artisan schedule:work to invoke the scheduler.
6. Start a new terminal/command prompt and navigate to the Golang folder with cd ABE command. Run go run main.go.
