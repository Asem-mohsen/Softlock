$(document).ready(function() {

    const chunkSize = 1024 * 1024;

    $('#fileInput').on('change', function() {
        let file = this.files[0];
        if (file) {
            uploadFileInChunks(file);
        }
    });

    function uploadFileInChunks(file) {
        let chunks = Math.ceil(file.size / chunkSize);
        let currentChunk = 0;

        function uploadNextChunk() {
            let start = currentChunk * chunkSize;
            let end = Math.min(file.size, start + chunkSize);
            let chunk = file.slice(start, end);

            let formData = new FormData();
            formData.append('chunk', chunk);
            formData.append('fileName', file.name);
            formData.append('chunkNumber', currentChunk);
            formData.append('totalChunks', chunks);

            axios.post(chunkRoute, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }).then(response => {
                console.log(response.data.message);
                currentChunk++;

                if (currentChunk < chunks) {
                    // More chunks to upload
                    uploadNextChunk();
                } else {
                    // File upload complete
                    console.log('File upload complete');
                }

                // Update progress
                let progress = (currentChunk / chunks) * 100;
                updateProgressBar(progress);

            }).catch(error => {
                console.error('Error uploading chunk:', error);
            });
        }

        // Start uploading chunks
        uploadNextChunk();
    }

    $('#saveEncryptedBtn').on('click', function() {
        var encryptedContent = $('#encryptedContentTextarea').val();
        var blob = new Blob([encryptedContent], { type: 'application/octet-stream' });
    
        saveFileWithUserPrompt(blob, 'encrypted', window.originalFileExtension);
    });
    
    function saveFileWithUserPrompt(blob, fileType, originalExtension) {
        var defaultFileName = `${fileType}_file.${originalExtension}`;
        var options = {
            suggestedName: defaultFileName,
            types: [{
                description: 'Encrypted File',
                accept: { 'application/octet-stream': ['.' + originalExtension] },
            }],
        };
    
        window.showSaveFilePicker(options)
            .then(handleSaveFile)
            .catch(handleError);
    
        function handleSaveFile(handle) {
            handle.createWritable()
                .then(writable => {
                    return writable.write(blob)
                        .then(() => writable.close());
                })
                .then(() => {
                    showSuccessMessage(`File was saved successfully as ${handle.name}`);
                    showFileDetails(new File([blob], handle.name, { type: 'application/octet-stream' }), null);
                })
                .catch(handleError);
        }
    
        function handleError(error) {
            showErrorMessage(error);
        }
    }

    // File Encryption
    $('#encryptBtn').click(function() {
        encryptFile();
    });

    function encryptFile() {
        const file = $('#fileInput')[0].files[0];

        if (file) {
            const formData = new FormData();
            formData.append('file', file);
            window.originalFileExtension = file.name.split('.').pop();
            uploadFileWithProgress(formData, encryptRoute);
        } else {
            showErrorMessage('Please select a file to encrypt.');
        }
    }

    $('#decryptBtn').click(function() {
        decryptFile();
    });

    $('#fileInput').on('change', function() {
        var file = this.files[0];
        var reader = new FileReader();

        reader.onload = function(e) {
            var fileContent = e.target.result;
            $('#encryptedContentTextarea').val(fileContent);

            showFileDetails(file, fileContent);
        }

        reader.readAsText(file);
    });

    function decryptFile() {
        const encryptedContent = $('#encryptedContentTextarea').val();
        const fileExtension = $('#fileExtension').text();

        if (encryptedContent) {
            const requestData = {
                encryptedContent: encryptedContent,
                fileName: 'decrypted_file.' + fileExtension 
            };

            $.ajax({
                url: decryptRoute,
                type: 'POST',
                data: JSON.stringify(requestData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        const decryptedContent = atob(response.decryptedContent);
                        
                        $('#decryptedContentTextarea').val(decryptedContent);
                        $('#decryptedFileName').text(response.fileName);
    
                        $('#decryptedContent').show();
    
                        showSuccessMessage('File decrypted successfully.');
    
                        updateFilePreview(decryptedContent, response.fileName);
                    } else {
                        showErrorMessage('Decryption failed: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorMessage(error);
                }
            });
        }
    }
    function updateFilePreview(content, fileName) {
        const fileExtension = fileName.split('.').pop().toLowerCase();
        const fileType = getFileType(fileExtension);
    
        let previewHtml = '';
    
        switch (fileType) {
            case 'image':
                previewHtml = `<img src="data:image/${fileExtension};base64,${btoa(content)}" alt="${fileName}" style="max-width: 100%; max-height: 400px;">`;
                break;
            case 'text':
                previewHtml = `<pre>${content}</pre>`;
                break;
            case 'pdf':
                previewHtml = `<p>PDF preview not available. Content decrypted successfully.</p>`;
                break;
            default:
                previewHtml = `<p>Preview not available for this file type. Content decrypted successfully.</p>`;
        }
    
        $('#filePreview').html(previewHtml);
    }
    
    function getFileType(extension) {
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        const textExtensions = ['txt', 'md', 'csv'];
        const videoExtensions = ['mp4', 'webm', 'ogg', 'mov', 'avi'];
        if (imageExtensions.includes(extension)) return 'image';
        if (textExtensions.includes(extension)) return 'text';
        if (videoExtensions.includes(extension)) return 'video';
        if (extension === 'pdf') return 'pdf';
        return 'other';
    }


    function showFileDetails(file, fileContent) {
        var formData = new FormData();
        formData.append('file', file);
        if (fileContent) {
            formData.append('content', fileContent);
        }
    
        $.ajax({
            url: detailsRoute,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                console.log(data)
                $('#fileName').text(data.name);
                $('#fileSize').text(data.size);
                $('#fileExtension').text(data.extension);
                
                if (data.preview) {
                    $('#filePreview').html(data.preview);
                    
                } else {
                    $('#filePreview').html('<p>Encrypted file content (preview not available)</p>');
                }
                
                $('#detailsSection').show();
            },
            error: function(xhr, status, error) {
                showErrorMessage('An error occurred: ' + error);
            }
        });
    }
    function showDecryptedFileDetails(tempFilePath) {
        const requestData = {
            tempFilePath: tempFilePath
        };

        $.ajax({
            url: detailsRoute,
            type: 'POST',
            data: JSON.stringify(requestData),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                if (data.error) {
                    showErrorMessage(data.error)
                } else {
                    const fileExtension = data.extension.toLowerCase();
                    const tempFilePath = data.tempFilePath;

                    if (fileExtension === 'pdf') {
                        $('#filePreview').html('<iframe src="'+ tempFilePath + '" width="100%" height="500px"></iframe>');
                    } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(fileExtension)) {
                        $('#filePreview').html('<img src="'+ tempFilePath + '" alt="' + data.name + '" style="max-width: 100%; max-height: 400px;">');
                    } else {
                        $('#filePreview').html('<a href="'+ tempFilePath + '" download>' + data.name + '</a>');
                    }

                    $('#fileName').text(data.name);
                    $('#fileSize').text(data.size);
                    $('#fileExtension').text(data.extension);
                    $('#detailsSection').show();
                }
            },
            error: function(xhr, status, error) {
                showErrorMessage(error)
            }
        });
    }

    function showSuccessMessage(message) {
        $('#successMessages .alert p').text(message);
        $('#successMessages').show().delay(9000).fadeOut();
    }

    function showErrorMessage(message) {
        $('#errorMessages .alert p').text(message);
        $('#errorMessages').show().delay(9000).fadeOut();
    }

    function uploadFileWithProgress(formData, url, originalExtension) {
        // Show progress bar
        $('.progress').show();

        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);

        // Set up event listeners
        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                var percentComplete = (event.loaded / event.total) * 100;
                updateProgressBar(percentComplete);
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                
                var data = JSON.parse(xhr.responseText);
                if (data.encryptedFileName) {
                    $('#encryptedFileName').text(data.encryptedFileName);
                    $('#encryptedContentTextarea').val(data.encryptedContent);
                    $('#encryptedContent').show();
                    $('#decryptBtn').show();
                    updateProgressBar(100);
                    setTimeout(() => {
                        $('.progress').hide();
                        updateProgressBar(0);
                    }, 1000);

                } else {
                    showErrorMessage('Encryption failed: ' + data.error);
                }
            } else {
                showErrorMessage('An error occurred: ' + xhr.statusText);
            }
        };

        xhr.onerror = function() {
            showErrorMessage('An error occurred during the upload.');
            $('.progress').hide();
        };

        // Add CSRF token to the request headers
        xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));

        xhr.send(formData);
    }

    function updateProgressBar(percentComplete) {
        $('#progressBar').css('width', percentComplete + '%')
                        .attr('aria-valuenow', percentComplete)
                        .text(Math.round(percentComplete) + '%');
    }
    
});

