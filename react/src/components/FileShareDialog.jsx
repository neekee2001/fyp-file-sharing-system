import { useEffect, useRef, useState } from "react";
import axiosClient from "../axios-client.js";
import { useStateContext } from "../contexts/ContextProvider.jsx";
import Alert from "@mui/material/Alert";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";
import DialogTitle from "@mui/material/DialogTitle";
import Grid from "@mui/material/Grid";
import MenuItem from "@mui/material/MenuItem";
import TextField from "@mui/material/TextField";

export default function FileShareDialog({ isOpen, onClose, fileId }) {
    const permissionIdRef = useRef();
    const deptIdRef = useRef();
    const [errors, setErrors] = useState(null);
    const [deptOptions, setDeptOptions] = useState([]);
    const [permissionOptions, setPermissionOptions] = useState([]);
    const { setNotification } = useStateContext();

    useEffect(() => {
        if (fileId) {
            getDepartments();
            getSharePermissions();
        }
    }, [fileId]);

    const getDepartments = () => {
        axiosClient
            .get("/departments-to-share")
            .then(({ data }) => {
                setDeptOptions(data);
            })
            .catch((err) => {
                console.error("Error fetching department data:", err);
            });
    };

    const getSharePermissions = () => {
        axiosClient
            .get("/permissions")
            .then(({ data }) => {
                setPermissionOptions(data);
            })
            .catch((err) => {
                console.error("Error fetching permission data:", err);
            });
    };

    const handleClose = () => {
        setErrors("");
        onClose();
    };

    const handleFileShare = () => {
        const payload = {
            file_id: fileId,
            shared_with_department_id: deptIdRef.current.value,
            permission_id: permissionIdRef.current.value,
        };

        axiosClient
            .post("/file/share", payload)
            .then((response) => {
                if (response && response.status == 201) {
                    handleClose();
                    setNotification(response.data.message);
                }
            })
            .catch((err) => {
                const response = err.response;
                if (
                    response &&
                    (response.status == 404 || response.status == 422)
                ) {
                    if (response.data.errors) {
                        setErrors(response.data.errors);
                    } else {
                        setErrors({
                            user: [response.data.message],
                        });
                    }
                    setTimeout(() => {
                        setErrors("");
                    }, 6000);
                }
            });
    };

    return (
        <Dialog
            open={isOpen}
            onClose={handleClose}
            scroll="paper"
            fullWidth
            maxWidth="sm"
        >
            <DialogTitle>Share</DialogTitle>
            <DialogContent dividers>
                {errors && (
                    <Alert severity="error" sx={{ alignItems: "center" }}>
                        {Object.keys(errors).map((key) => (
                            <p key={key} style={{ margin: "5px" }}>
                                {errors[key][0]}
                            </p>
                        ))}
                    </Alert>
                )}
                <Grid sx={{ mb: 2 }}>
                    <TextField
                        id="dept_id"
                        select
                        label="Select Department to Share"
                        defaultValue=""
                        inputRef={deptIdRef}
                        variant="filled"
                        margin="dense"
                        fullWidth
                        required
                    >
                        {deptOptions.map((dept) => (
                            <MenuItem key={dept.id} value={dept.id}>
                                {dept.dep_name}
                            </MenuItem>
                        ))}
                    </TextField>
                    <TextField
                        id="permission_id"
                        select
                        label="Permission"
                        defaultValue=""
                        inputRef={permissionIdRef}
                        variant="filled"
                        margin="dense"
                        fullWidth
                        required
                    >
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
                <Button onClick={handleFileShare}>Share</Button>
            </DialogActions>
        </Dialog>
    );
}
