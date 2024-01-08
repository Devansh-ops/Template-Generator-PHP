document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('templateForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevents the default form submission action

        // Creating a FormData object to hold the form data
        var formData = new FormData();
        formData.append('recipientName', document.getElementById('recipientName').value);
        formData.append('emailSubject', document.getElementById('emailSubject').value);
        formData.append('keyPoints', document.getElementById('keyPoints').value);
        formData.append('tone', document.getElementById('tone').value);
        formData.append('additionalInstructions', document.getElementById('additionalInstructions').value);

        // Sending the data to generateTemplate.php
        fetch('generateTemplate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Displaying the generated email template in the textarea
            document.getElementById('editableTemplate').value = data.generatedTemplate;

            // Make the template output visible
            document.getElementById('templateOutput').style.display = 'block';
        })
        .catch((error) => {
            console.error('Error:', error);
        });
    });
});
