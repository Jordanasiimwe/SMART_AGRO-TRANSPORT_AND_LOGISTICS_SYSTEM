    <footer class="main-footer no-print">
        <p>&copy; <?php echo date('Y'); ?> Icarit David Julius, Asiimwe Jordan, Nahigo Racheal, Nagginda Shirat and Tusiime Rhoda | Class of 2026</p>
        <p style="margin-top: 5px; font-size: 0.85rem;">
            <a href="/feedback" style="color: inherit; text-decoration: underline;">Give Feedback</a>
        </p>
    </footer>

    <!-- SMS Modal -->
    <div id="smsModal" class="modal no-print">
        <div class="modal-content">
            <span class="close-btn" onclick="closeSmsModal()">&times;</span>
            <h3>Send SMS</h3>
            <p>To: <strong id="smsRecipientName"></strong></p>
            <form id="smsForm" onsubmit="sendSms(event)">
                <input type="hidden" id="smsRecipientId" name="recipient_id">
                <div class="form-group">
                    <label for="smsMessage">Message</label>
                    <textarea id="smsMessage" name="message" rows="5" required maxlength="160"></textarea>
                    <small><span id="smsCharCount">160</span> characters remaining</small>
                </div>
                <button type="submit">Send Message</button>
                <div id="smsStatus" style="margin-top: 1rem;"></div>
            </form>
        </div>
    </div>

    <script>
    // SMS Modal Logic
    const smsModal = document.getElementById('smsModal');
    const smsRecipientName = document.getElementById('smsRecipientName');
    const smsRecipientId = document.getElementById('smsRecipientId');
    const smsMessage = document.getElementById('smsMessage');
    const smsStatus = document.getElementById('smsStatus');
    const smsCharCount = document.getElementById('smsCharCount');

    function openSmsModal(recipientId, recipientName) {
        smsRecipientId.value = recipientId;
        smsRecipientName.innerText = recipientName;
        smsMessage.value = '';
        smsStatus.innerHTML = '';
        smsCharCount.innerText = '160';
        smsModal.style.display = 'block';
    }

    function closeSmsModal() {
        smsModal.style.display = 'none';
    }

    if (smsMessage) {
        smsMessage.addEventListener('input', () => {
            const remaining = 160 - smsMessage.value.length;
            smsCharCount.innerText = remaining;
        });
    }

    function sendSms(e) {
        e.preventDefault();
        smsStatus.innerHTML = '<p class="info">Sending...</p>';
        const formData = new FormData(e.target);

        fetch('/api/send-sms', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if the response is OK (status 200-299) and is JSON.
            if (response.ok && response.headers.get("Content-Type")?.includes("application/json")) {
                return response.json();
            }
            // If not, get the response as text to see the underlying PHP error.
            return response.text().then(text => {
                // Throw an error to be caught by the .catch() block below.
                // Pass an object with the raw text from the server for debugging.
                throw { message: "Server returned a non-JSON response.", serverResponse: text };
            });
        })
        .then(data => {
            const messageClass = data.success ? 'success' : 'error';
            smsStatus.innerHTML = `<p class="${messageClass}">${data.message}</p>`;
            if (data.success) {
                setTimeout(closeSmsModal, 2000);
            }
        })
        .catch(error => {
            smsStatus.innerHTML = '<p class="error">An unexpected error occurred. Please check the browser console for details.</p>';
            
            // Log the detailed error to the console for debugging.
            console.error('SMS Sending Error:', error);
            // If we have the raw server response, log it. This is the most helpful part.
            if (error.serverResponse) {
                console.error('Raw Server Response:', error.serverResponse);
            }
        });
    }

    // Close modal if clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target == smsModal) {
            closeSmsModal();
        }
    });
    </script>
</body>
</html>