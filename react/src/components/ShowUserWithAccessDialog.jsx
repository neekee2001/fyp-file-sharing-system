import { useEffect, useState } from "react";
import axiosClient from "../axios-client.js";
import { useStateContext } from "../contexts/ContextProvider.jsx";
import UpdateShareAccessDialog from "./UpdateShareAccessDialog.jsx";
import Loading from "../components/Loading.jsx";
import Alert from "@mui/material/Alert";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";
import DialogTitle from "@mui/material/DialogTitle";
import Divider from "@mui/material/Divider";
import List from "@mui/material/List";
import ListItem from "@mui/material/ListItem";
import ListItemText from "@mui/material/ListItemText";
import Typography from "@mui/material/Typography";

export default function ShowUserWithAccessDialog({ isOpen, onClose, fileId }) {
    const [errors, setErrors] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [permissionOptions, setPermissionOptions] = useState([]);
    const [viewers, setViewers] = useState([]);
    const [editors, setEditors] = useState([]);
    const [selectedSharedFileId, setSelectedSharedFileId] = useState(null);
    const [updateAccessDialogOpen, setUpdateAccessDialogOpen] = useState(false);
    const { setNotification } = useStateContext();

    useEffect(() => {
        if (isOpen && fileId) {
            setIsLoading(true);
            getViewers();
            getEditors();
            getSharePermissions();
        }
    }, [isOpen, fileId]);

    const getViewers = () => {
        axiosClient
            .get("/users-with-viewer-access/" + fileId)
            .then(({ data }) => {
                setViewers(data);
            })
            .catch((err) => {
                console.error("Error fetching viewers data:", err);
            });
    };

    const getEditors = () => {
        axiosClient
            .get("/users-with-editor-access/" + fileId)
            .then(({ data }) => {
                setEditors(data);
            })
            .catch((err) => {
                console.error("Error fetching editors data:", err);
            });
    };

    const getSharePermissions = () => {
        axiosClient
            .get("/permissions")
            .then(({ data }) => {
                setPermissionOptions(data);
                setIsLoading(false);
            })
            .catch((err) => {
                console.error("Error fetching permission data:", err);
                setIsLoading(false);
            });
    };

    const handleUpdateAccessDialogOpen = (ev, id) => {
        setSelectedSharedFileId(id);
        setUpdateAccessDialogOpen(true);
    };

    const handleUpdateAccessDialogClose = () => {
        setSelectedSharedFileId(null);
        setUpdateAccessDialogOpen(false);
        handleClose();
    };

    const handleClose = () => {
        setErrors("");
        onClose();
    };

    const handleRevokeFileAccess = (ev, id) => {
        const payload = {
            shared_file_id: id,
        };

        axiosClient
            .post("/file/revoke-file-access", payload)
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
                        setErrors("");
                    }, 6000);
                }
            });
    };

    if (isLoading) {
        return <Loading />;
    }

    return (
        <Dialog
            open={isOpen}
            onClose={handleClose}
            scroll="paper"
            fullWidth
            maxWidth="sm"
        >
            <DialogTitle>Users with Access</DialogTitle>
            <DialogContent dividers>
                {errors && (
                    <Alert severity="error" sx={{ alignItems: "center" }}>
                        {errors}
                    </Alert>
                )}
                <List>
                    <Typography variant="button" display="block" gutterBottom>
                        Viewer
                    </Typography>
                    <Divider />
                    {viewers.map((viewer, index) => (
                        <div key={viewer.id}>
                            {index === 0 ||
                            viewers[index - 1].dep_name !== viewer.dep_name ? (
                                <ListItem disablePadding>
                                    <ListItemText primary={viewer.dep_name} />
                                </ListItem>
                            ) : null}

                            <ListItem disablePadding>
                                <ListItemText
                                    secondary={`${viewer.name} - ${viewer.email}`}
                                />
                                <Button
                                    color="secondary"
                                    onClick={(ev) =>
                                        handleUpdateAccessDialogOpen(
                                            ev,
                                            viewer.id
                                        )
                                    }
                                >
                                    Edit
                                </Button>
                                <Button
                                    color="error"
                                    onClick={(ev) =>
                                        handleRevokeFileAccess(ev, viewer.id)
                                    }
                                >
                                    Remove
                                </Button>
                            </ListItem>
                        </div>
                    ))}
                    <Typography variant="button" display="block" sx={{ mt: 1 }}>
                        Editor
                    </Typography>
                    <Divider />
                    {editors.map((editor, index) => (
                        <div key={editor.id}>
                            {index === 0 ||
                            editors[index - 1].dep_name !== editor.dep_name ? (
                                <ListItem disablePadding>
                                    <ListItemText primary={editor.dep_name} />
                                </ListItem>
                            ) : null}

                            <ListItem disablePadding>
                                <ListItemText
                                    secondary={`${editor.name} - ${editor.email}`}
                                />
                                <Button
                                    color="secondary"
                                    onClick={(ev) =>
                                        handleUpdateAccessDialogOpen(
                                            ev,
                                            editor.id
                                        )
                                    }
                                >
                                    Edit
                                </Button>
                                <Button
                                    color="error"
                                    onClick={(ev) =>
                                        handleRevokeFileAccess(ev, editor.id)
                                    }
                                >
                                    Remove
                                </Button>
                            </ListItem>
                        </div>
                    ))}
                </List>
                <UpdateShareAccessDialog
                    isOpen={updateAccessDialogOpen}
                    onClose={handleUpdateAccessDialogClose}
                    permissionOptions={permissionOptions}
                    sharedFileId={selectedSharedFileId}
                />
            </DialogContent>
            <DialogActions>
                <Button onClick={handleClose}>Close</Button>
            </DialogActions>
        </Dialog>
    );
}
