import { useEffect, useRef, useState } from 'react';
import axiosClient from '../axios-client.js';
import { useStateContext } from '../contexts/ContextProvider.jsx';
import { styled } from '@mui/material/styles';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Grid from '@mui/material/Grid';
import IconButton from '@mui/material/IconButton';
import Paper from '@mui/material/Paper';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import Typography from '@mui/material/Typography';
import FileUploadIcon from '@mui/icons-material/FileUpload';
import MoreVertIcon from '@mui/icons-material/MoreVert';

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

export default function MyFiles() {
    const fileUploadRef = useRef();
    const [errors, setErrors] = useState(null);
    const [files, setFiles] = useState([]);
    const {setNotification} = useStateContext();

    useEffect(() => {
        getFiles();
    }, [])

    const getFiles = () => {
        axiosClient.get('/myfiles')
            .then(({data}) => {
                setFiles(data);
            })
            .catch((err) => {
                console.error('Error fetching file data:', err);
            })
    }

    const handleFileUpload = (ev) => {
        const fileObj = ev.target.files[0];

        const formData = new FormData();
        formData.append('file', fileObj);

        axiosClient.post('/file/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
            .then((response) => {
                if (response && response.status == 201) {
                    setNotification(response.data.message)
                    getFiles()
                }
            })
            .catch((err) => {
                const response = err.response;
                if (response && response.status == 422) {
                    setErrors(response.data.message)
                    setTimeout(() => {
                        setErrors('')
                    }, 6000)
                }
            })

        fileUploadRef.current.value = null;
    }
    
    return (
        <Grid>
            <Grid sx={{ mb: 3, display: 'flex', }}>
                <Typography variant="h6" component="div" sx={{ py: 0.5, flexGrow: 1, }}>
                    My Files
                </Typography>
                <Button component="label" variant="contained" startIcon={<FileUploadIcon />}>
                    Upload
                    <VisuallyHiddenInput ref={fileUploadRef} type="file" onChange={handleFileUpload} />
                </Button>
            </Grid>
            {errors && <Alert severity="error" sx={{ alignItems: 'center', }}>
                {errors}
            </Alert>
            }
            <Paper elevation={4} sx={{ my: 2, }}>
                <TableContainer sx={{ maxHeight: 450, }}>
                    <Table stickyHeader>
                        <TableHead>
                            <TableRow>
                                <TableCell sx={{ width: '80%', }}>Name</TableCell>
                                <TableCell sx={{ width: '15%', }}>Last Modified</TableCell>
                                <TableCell sx={{ width: '5%', }}></TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {files.map((file) => (
                                <TableRow key={file.id}>
                                    <TableCell>{file.file_name}</TableCell>
                                    <TableCell>{file.updated_at}</TableCell>
                                    <TableCell>
                                        <IconButton aria-label="menu" size="small">
                                            <MoreVertIcon fontSize="inherit" />
                                        </IconButton>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableContainer>
            </Paper>
        </Grid>
    )
}