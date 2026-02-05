<?php

use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

function bleuh_sftp_download($localFilePath) {
    // Create an SFTP connection
    $sftp = new SFTP(METROGREEN_URL);

    // Load the private key with optional passphrase
    $privateKey = PublicKeyLoader::load(file_get_contents(METROGREEN_RSA), METROGREEN_PASSPHRASE);

    // Use the private key to authenticate with the server
    if (!$sftp->login(METROGREEN_USER, $privateKey)) {
        bleuh_log('Authentication failed');
        return false;
    }

    $files = $sftp->rawlist(METROGREEN_REMOTE_DIR);
    if ($files === false) {
        bleuh_log('Failed to list files in the directory');
        return false;
    }

    // Initialize variables to track the latest file
    $latestFile = null;
    $latestFileTime = 0;

    // Loop through the files to find the latest one
    foreach ($files as $file => $details) {
        // Skip directories (check file type using the `type` field)
        if ($details['type'] !== 1) { // 1 = regular file, 2 = directory
            continue;
        }

        // Compare modification times (`mtime` = last modified time)
        if ($details['mtime'] > $latestFileTime) {
            $latestFile = $file;
            $latestFileTime = $details['mtime'];
        }
    }

    // Check if a latest file was found
    if (!$latestFile) {
        bleuh_log('No files found in the directory');
        return false;
    }

    // Full path to the latest file on the remote server
    $remoteFilePath = METROGREEN_REMOTE_DIR . '/' . $latestFile;

    // Download the latest file
    if (!$sftp->get($remoteFilePath, $localFilePath)) {
        bleuh_log('Download failed');
        return false;
    }

    bleuh_log("Successfully downloaded the latest file: $latestFile to $localFilePath");
    return $latestFileTime;

}
