import { Link, Navigate, Outlet } from "react-router-dom";
import { useStateContext } from "../contexts/ContextProvider.jsx";
import { useEffect, useState } from "react";
import axiosClient from "../axios-client.js";
import Logo from "../images/logo.png";
import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Drawer from '@mui/material/Drawer';
import Grid from '@mui/material/Grid';
import List from '@mui/material/List';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemText from '@mui/material/ListItemText';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import Toolbar from '@mui/material/Toolbar';
import Typography from '@mui/material/Typography';

export default function DefaultLayout() {
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [anchorEl, setAnchorEl] = useState(null);
    const {user, token, setUser, setToken, notification} = useStateContext();

    useEffect(() => {
        axiosClient.get('/user')
            .then(({data}) => {
                setUser(data)
            })
    }, [])

    if (!token) {
        return <Navigate to="/login" />
    }

    const handleUserIconOpen = (ev) => {
        setAnchorEl(ev.currentTarget);
    }
    
    const handleUserIconClose = () => {
        setAnchorEl(null);
    }

    const handleProfileClick = () => {
        setAnchorEl(null);
        setSelectedIndex(null);
    }

    const handleListItemClick = (ev, index) => {
        setSelectedIndex(index);
    }

    const onLogOut = (ev) => {
        ev.preventDefault()
        axiosClient.post('/logout')
            .then(() => {
                setUser({})
                setToken(null)
            })
    }

    return (
        <Grid sx={{ display: 'flex' }}>
            <AppBar position="fixed" sx={{ zIndex: (theme) => theme.zIndex.drawer + 1 }}> 
                <Toolbar>
                    <Box component="img" sx={{ mr: 1.5, height: 1/40, width: 1/40, }} src={Logo} />
                    <Typography variant="button" noWrap component="div" sx={{ flexGrow: 1 }}>
                        Jabatan Kemajuan Islam Malaysia (JAKIM)
                    </Typography>
                    <Button aria-controls="menu-list" aria-haspopup="true" onClick={handleUserIconOpen} variant="outlined" color="inherit">
                        {user.name}
                    </Button>
                    <Menu 
                        id="menu-list"
                        anchorEl={anchorEl}
                        open={Boolean(anchorEl)}
                        onClose={handleUserIconClose}
                        >
                        <MenuItem onClick={handleProfileClick} component={Link} to="/profile">Profile</MenuItem>
                        <MenuItem onClick={onLogOut}>Logout</MenuItem>
                    </Menu>
                </Toolbar>
            </AppBar>
            <Drawer variant="permanent" sx={{ width: 240, flexShrink: 0, [`& .MuiDrawer-paper`]: { width: 240, boxSizing: 'border-box' }, }}>
                <Toolbar />
                <Grid>
                    <List component="nav">
                        <ListItemButton selected={selectedIndex === 0} onClick={(ev) => handleListItemClick(ev, 0)} component={Link} to="/myfiles">
                            <ListItemText primary="My Files" sx={{ px: 1, }} />
                        </ListItemButton>
                        <ListItemButton selected={selectedIndex === 1} onClick={(ev) => handleListItemClick(ev, 1)} component={Link} to="/sharedwithme">
                            <ListItemText primary="Shared With Me" sx={{ px: 1, }} />
                        </ListItemButton>
                    </List>
                </Grid>
            </Drawer>
            <Grid component="main" sx={{ flexGrow: 1, p: 3, }}>
                <Toolbar />
                <Outlet />
            </Grid>
        </Grid>

        // TODO: set notification message            
        //         {notification && <div className="notification">
        //             {notification}
        //         </div>
        //         }
    )
}