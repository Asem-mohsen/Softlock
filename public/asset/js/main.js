$(document).ready(function() {

    $('#saveEncryptedBtn').on('click', function() {
        var encryptedContent = $('#encryptedContentTextarea').val();
        var blob = new Blob([encryptedContent], { type: 'text/plain' });

        saveFileWithUserPrompt(blob, 'encrypted');
    });

    function saveFileWithUserPrompt(blob, fileType) {
        var defaultFileName = `${fileType}_file.txt`;
        var options = {
            types: [{
                description: 'Text Files',
                accept: { 'text/plain': ['.txt'] },
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
                    showSuccessMessage(`File was saved successfully.`);
                })
                .catch(handleError);
        }

        function handleError(error) {
            showErrorMessage(error);
        }
    }

    function showSaveFilePickerAndSave(blob, suggestedName, options) {
        window.showSaveFilePicker(options)
            .then(handleSaveFile)
            .catch(handleError);

        function handleSaveFile(handle) {
            var writable = handle.createWritable();
            writable.write(blob)
                .then(() => writable.close())
                .then(() => {
                    showSuccessMessage(`File was saved as: ${handle.name}`);
                })
                .catch(handleError);
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

            $.ajax({
                url: encryptRoute,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.encryptedFileName) {
                        $('#encryptedFileName').text(data.encryptedFileName);
                        $('#encryptedContentTextarea').val(data.encryptedContent);
                        $('#encryptedContent').show();
                        $('#decryptBtn').show();
                    } else {
                        showErrorMessage('Encryption failed: ' + data.error);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorMessage('An error occurred: ' + error);
                }
            });
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

            // check if the file is encrypted or not
            if (isEncryptedFile(fileContent)) {

                // already encrypted
                showFileDetails(file, fileContent);
                $('#decryptBtn').show();
            } else {
                // not encrypted
                showFileDetails(file, fileContent);
                $('#decryptBtn').hide();
            }
        }

        reader.readAsText(file);
    });

    function decryptFile() {
        const encryptedContent = $('#encryptedContentTextarea').val();
        const fileExtension = $('#fileExtension').text();

        if (encryptedContent) {
            const requestData = {
                encryptedContent: encryptedContent,
                extension: fileExtension
            };

            $.ajax({
                url: decryptRoute,
                type: 'POST',
                data: JSON.stringify(requestData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    if (data.success) {
                        const tempFilePath = data.tempFilePath;
                        showDecryptedFileDetails(tempFilePath);
                    } else {
                        showErrorMessage(data.error);
                    }
                },
                error: function(xhr, status, error) {
                    showErrorMessage(error);
                }
            });
        }
    }

    function isEncryptedFile(fileContent) {
        var keyHeaderRegex = /^KEY:/;
        return keyHeaderRegex.test(fileContent);
    }

    // function to display the file details
    function showFileDetails(file, fileContent) {
        var formData = new FormData();
        formData.append('file', file);
        formData.append('content', fileContent);

        $.ajax({
            url: detailsRoute,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                $('#fileName').text(data.name);
                $('#fileSize').text(data.size);
                $('#fileExtension').text(data.extension);
                $('#filePreview').html(data.preview);
                $('#detailsSection').show();
            },
            error: function(xhr, status, error) {
                showErrorMessage('An error occurred: '. error)
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
        $('#successMessages').show().delay(5000).fadeOut();
    }

    function showErrorMessage(message) {
        $('#errorMessages .alert p').text(message);
        $('#errorMessages').show().delay(5000).fadeOut();
    }
});

