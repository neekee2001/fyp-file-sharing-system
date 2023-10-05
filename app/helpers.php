<?php

if (!function_exists('ipfs')) 
{
    function ipfs()
    {
        $host = env('IPFS_HOST');
        $port = env('IPFS_PORT');
        $apiPort = env('IPFS_API_PORT');
    
        return new \rannmann\PhpIpfsApi\IPFS($host, $port, $apiPort);
    }
}
