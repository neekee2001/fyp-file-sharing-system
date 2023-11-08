import { useRef, useState } from 'react';
import axiosClient from '../axios-client.js';
import { useStateContext } from '../contexts/ContextProvider.jsx';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Grid from '@mui/material/Grid';
import MenuItem from '@mui/material/MenuItem';
import TextField from '@mui/material/TextField';

export default function UpdateShareAccessDialog({ isOpen, onClose, permissionOptions, sharedFileId }) {
    const permissionIdRef = useRef();

    const [errors, setErrors] = useState(null);
    const {setNotification} = useStateContext();

    const handleClose = () => {
        setErrors('');
        onClose();
    }

    const handleUpdateFileAccess = () => {
        const payload = {
            shared_file_id: sharedFileId,
            shared_permission_id: permissionIdRef.current.value,
        }

        axiosClient.post('/file/update-file-access', payload)
            .then((response) => {
                if (response && response.status == 200) {
                    handleClose();
                    setNotification(response.data.message);
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
    }

    return (
        <Dialog open={isOpen} onClose={handleClose} fullWidth maxWidth="sm">
            <DialogTitle>
                Edit Access
            </DialogTitle>
            <DialogContent dividers>
                {errors && <Alert severity="error" sx={{ alignItems: 'center', }}>
                    {errors}
                </Alert>
                }
                <Grid sx={{ mb: 2, }}>
                    <TextField id="permission_id" select label="Permission" defaultValue="" inputRef={permissionIdRef} variant="filled" margin="dense" fullWidth required>
                        {permissionOptions.map((permission) => (
                            <MenuItem key={permission.id} value={permission.id}>
                                {permission.permission_name}
                            </MenuItem>
                        ))}
                    </TextField>
                </Grid>
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose}>Cancel</Button>
                <Button onClick={handleUpdateFileAccess}>Confirm</Button>
            </DialogActions>
        </Dialog>
    )
}