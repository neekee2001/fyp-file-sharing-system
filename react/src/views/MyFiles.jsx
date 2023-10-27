import { useEffect, useRef, useState } from 'react';
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
import IconButton from '@mui/material/IconButton';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import Paper from '@mui/material/Paper';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import TextField from '@mui/material/TextField';
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
    const permissionIdRef = useRef();
    const userIdRef = useRef();

    const [errors, setErrors] = useState(null);
    const [files, setFiles] = useState([]);
    const [selectedFileId, setSelectedFileId] = useState(0);
    const [anchorEl, setAnchorEl] = useState(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [dialogErrors, setDialogErrors] = useState(null);
    const [userOptions, setUserOptions] = useState([]);
    const [permissionOptions, setPermissionOptions] = useState([]);
    const {setNotification} = useStateContext();

    useEffect(() => {
        getFiles();
    }, [])

    const handleMoreIconOpen = (ev, id) => {
        setAnchorEl(ev.currentTarget);
        setSelectedFileId(id);
    }

    const handleMoreIconClose = () => {
        setAnchorEl(null);
        setSelectedFileId(0);
    }

    const handleShareDialogOpen = () => {
        getUsers();
        getSharePermissions();
        setDialogOpen(true);
    }

    const handleShareDialogClose = () => {
        handleMoreIconClose();
        setDialogOpen(false);
    }

    const getFiles = () => {
        axiosClient.get('/myfiles')
            .then(({data}) => {
                setFiles(data);
            })
            .catch((err) => {
                console.error('Error fetching file data:', err);
            })
    }

    const getUsers = () => {
        axiosClient.get('/users-to-share')
            .then(({data}) => {
                setUserOptions(data);
            })
            .catch((err) => {
                console.error('Error fetching user data:', err);
            })
    }

    const getSharePermissions = () => {
        axiosClient.get('/permissions')
            .then(({data}) => {
                setPermissionOptions(data);
            })
            .catch((err) => {
                console.error('Error fetching permission data:', err);
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
                    setNotification(response.data.message);
                    getFiles();
                }
            })
            .catch((err) => {
                const response = err.response;
                if (response && response.status == 422) {
                    setErrors(response.data.message);
                    setTimeout(() => {
                        setErrors('');
                    }, 6000);
                }
            })

        fileUploadRef.current.value = null;
    }

    const handleFileShare = () => {
        const payload = {
            file_id: selectedFileId,
            shared_with_user_id: userIdRef.current.value,
            permission_id: permissionIdRef.current.value,
        }

        axiosClient.post('/file/share', payload)
            .then((response) => {
                if (response && response.status == 201) {
                    handleShareDialogClose();
                    handleMoreIconClose();
                    setNotification(response.data.message);
                }
            })
            .catch((err) => {
                const response = err.response;
                if (response && (response.status == 404 || response.status == 422)) {
                    setDialogErrors(response.data.message);
                    setTimeout(() => {
                        setDialogErrors('');
                    }, 6000);
                }
            })
    }

    const handleFileDownload = () => {
        const fileId = selectedFileId;
        
        axiosClient.get('/file/download-myfiles/' + fileId, {
            responseType: 'blob',
        })
            .then((response) => {
                handleMoreIconClose();
                const href = window.URL.createObjectURL(response.data);
                const anchorElement = document.createElement('a');
                anchorElement.href = href;
                const contentDisposition = response.headers['content-disposition'];
                
                let fileName = "";

                if (contentDisposition) {
                    const fileNameMatch = contentDisposition.match(/filename="(.+)"/);

                    if (fileNameMatch.length === 2) {
                        fileName = fileNameMatch[1];
                    }
                }

                anchorElement.download = fileName;
                document.body.appendChild(anchorElement);
                anchorElement.click();
                document.body.removeChild(anchorElement);
                window.URL.revokeObjectURL(href);
            })
            .catch((err) => {
                const response = err.response;
                if (response && response.status == 404) {
                    setErrors(response.data.message);
                    setTimeout(() => {
                        setErrors('');
                    }, 6000);
                }
            })
    }
    
    const handleFileDelete = () => {
        const fileId = selectedFileId;

        axiosClient.delete('/file/delete/' + fileId)
            .then((response) => {
                if (response && response.status == 200) {
                    handleMoreIconClose();
                    setNotification(response.data.message);
                    getFiles();
                }
            })
            .catch((err) => {
                const response = err.response;
                if (response && response.status == 404) {
                    setErrors(response.data.message);
                    setTimeout(() => {
                        setErrors('');
                    }, 6000);
                }
            })
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
            <Paper elevation={4} sx={{ my: 2, height: 450, }}>
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
                                        <IconButton aria-label="more" aria-controls="menu-list" aria-haspopup="true" onClick={(ev) => handleMoreIconOpen(ev, file.id)} size="small">
                                            <MoreVertIcon fontSize="inherit" />
                                        </IconButton>
                                    </TableCell>
                                </TableRow>
                            ))}
                            <Menu 
                                id="menu-list"
                                anchorEl={anchorEl}
                                open={Boolean(anchorEl)}
                                onClose={handleMoreIconClose}
                                >
                                <MenuItem onClick={handleShareDialogOpen}>Share</MenuItem>
                                <MenuItem>Edit</MenuItem>
                                <MenuItem onClick={handleFileDownload}>Download</MenuItem>
                                <MenuItem onClick={handleFileDelete}>Delete</MenuItem>
                            </Menu>
                        </TableBody>
                    </Table>
                </TableContainer>
            </Paper>
            <Dialog open={dialogOpen} onClose={handleShareDialogClose} fullWidth maxWidth="sm">
                <DialogTitle>
                    Share
                </DialogTitle>
                <DialogContent dividers>
                    {dialogErrors && <Alert severity="error" sx={{ alignItems: 'center', }}>
                        {dialogErrors}
                    </Alert>
                    }
                    <TextField id="user_id" select label="Add user" defaultValue="" inputRef={userIdRef} variant="filled" margin="dense" fullWidth required>
                        {userOptions.map((user) => (
                            <MenuItem key={user.id} value={user.id}>
                                {user.email}
                            </MenuItem>
                        ))}
                    </TextField>
                    <TextField id="permission_id" select label="Permission" defaultValue="" inputRef={permissionIdRef} variant="filled" margin="dense" fullWidth required>
                        {permissionOptions.map((permission) => (
                            <MenuItem key={permission.id} value={permission.id}>
                                {permission.permission_name}
                            </MenuItem>
                        ))}
                    </TextField>
                </DialogContent>
                <DialogActions>
                    <Button onClick={handleShareDialogClose}>Cancel</Button>
                    <Button onClick={handleFileShare}>Share</Button>
                </DialogActions>
            </Dialog>
        </Grid>
    )
}