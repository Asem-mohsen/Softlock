
$(document).ready(function() {

    $('#saveEncryptedBtn').on('click', function() {
        var encryptedContent = $('#encryptedContentTextarea').val();
        var key = $('#key').val();
        var fileContent = `KEY: ${key}\n${encryptedContent}`;
        var blob = new Blob([fileContent], { type: 'text/plain' });

        saveFileWithUserPrompt(blob, 'encrypted');
    });

    $('#saveDecryptedBtn').on('click', function() {
        var decryptedContent = $('#decryptedContentTextarea').val();
        var blob = new Blob([atob(decryptedContent)], { type: 'text/plain' });

        promptForFileNameAndSave(blob, 'decrypted');
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
                    alert(`File was saved successfully.`);
                })
                .catch(handleError);
        }

        function handleError(error) {
            console.error(error);
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
                    alert(`File was saved as: ${handle.name}`);
                })
                .catch(handleError);
        }

        function handleError(error) {
            console.error(error);
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
                        $('#key').val(data.key);
                        $('#encryptedContent').show();
                        $('#decryptBtn').show();
                    } else {
                        alert('Encryption failed: ' + data.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        } else {
            alert('Please select a file to encrypt.');
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
                extractKeyFromFile(fileContent);
                showFileDetails(file, fileContent);
                $('#decryptBtn').show();
            } else {
                // not encrypted
                extractKeyFromFile(fileContent);
                showFileDetails(file, fileContent);
                $('#decryptBtn').hide();
            }
        }

        reader.readAsText(file);
    });

    function decryptFile() {
        const encryptedContent = $('#encryptedContentTextarea').val();
        const key = $('#key').val();

        if (encryptedContent && key) {
            const formData = new FormData();
            formData.append('encryptedContent', encryptedContent);
            formData.append('key', key);

            $.ajax({
                url: decryptRoute,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(data) {
                    if (data.decryptedContent) {
                        const decryptedContent = atob(data.decryptedContent);
                        console.log(decryptedContent);
                    } else {
                        alert('Decryption failed: ' + data.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error);
                }
            });
        }
    }

    function extractKeyFromFile(fileContent) {
        const lines = fileContent.split(/\r?\n/);
        console.log(lines);
        var keyHeaderRegex = /^KEY:/;

        if (lines.length >= 2) {
            const key = lines[0];
            $('#key').val(key);
            const content = lines.slice(1).join('\n');
        } else {
            alert('Encryption key not found in the file content.');
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
                alert('An error occurred: ' + error);
            }
        });
    }

});

