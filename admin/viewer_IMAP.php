<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMAP Email Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        #email-list {
            max-height: 70vh;
            overflow-y: auto;
        }

        .email-body-plaintext {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .attachment-link:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-3xl border border-gray-100">

        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <h1 class="text-3xl font-extrabold text-gray-900">
                    <span class="text-blue-600">📥</span> Unread Inbox
                </h1>
            </div>

            <div id="status" class="text-sm font-semibold text-gray-600 flex items-center">
                <span id="loading-spinner"
                    class="animate-spin h-4 w-4 rounded-full border-2 border-r-transparent border-indigo-500 inline-block align-middle mr-2 hidden"></span>
                <span id="status-text" class="text-indigo-600">Ready</span>
            </div>
        </div>

        <div id="email-list" class="space-y-3">
            </div>

        <div id="no-emails-message" class="text-center text-gray-400 p-12 hidden">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-4 text-lg font-medium text-gray-600">All caught up! 🎉</p>
            <p class="text-sm text-gray-500">No new unseen emails found in your inbox.</p>
        </div>
    </div>

    <div id="email-modal"
        class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl h-4/5 flex flex-col overflow-hidden">

            <div class="p-4 border-b border-gray-200 flex justify-between items-center flex-shrink-0">
                <h2 id="modal-subject" class="text-xl font-bold text-gray-800 truncate pr-4">Email Subject</h2>
                <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-4 text-sm bg-gray-50 border-b border-gray-100 flex-shrink-0">
                <p class="font-medium text-gray-600">From: <span id="modal-from"
                        class="font-normal text-blue-600"></span></p>
                <p class="font-medium text-gray-600">Date: <span id="modal-date"
                        class="font-normal text-gray-500"></span></p>
            </div>
            
            <div id="modal-attachments-container" class="px-4 pt-4 hidden flex-shrink-0 border-b border-gray-100">
                <p class="text-xs font-semibold uppercase text-gray-500 mb-2">Attachments</p>
                <div id="modal-attachments" class="flex flex-wrap gap-2 pb-4">
                    </div>
            </div>

            <div class="p-4 flex-grow overflow-y-auto text-gray-700 leading-relaxed">
                <div id="modal-body-content">Loading full email content...</div>
            </div>

        </div>
    </div>

    <script>
        // --- UI Elements ---
        const emailList = document.getElementById('email-list');
        const statusText = document.getElementById('status-text');
        const loadingSpinner = document.getElementById('loading-spinner');
        const noEmailsMessage = document.getElementById('no-emails-message');

        // Modal Elements
        const emailModal = document.getElementById('email-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const modalSubject = document.getElementById('modal-subject');
        const modalFrom = document.getElementById('modal-from');
        const modalDate = document.getElementById('modal-date');
        const modalAttachmentsContainer = document.getElementById('modal-attachments-container');
        const modalAttachments = document.getElementById('modal-attachments');
        const modalBodyContent = document.getElementById('modal-body-content');


        // --- Utility Functions ---

        // Converts bytes to a human-readable size (e.g., 1024 -> 1 KB)
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        // --- Event Listeners for Modal ---

        closeModalBtn.addEventListener('click', () => {
            emailModal.classList.add('hidden');
        });

        emailModal.addEventListener('click', (e) => {
            if (e.target === emailModal) {
                emailModal.classList.add('hidden');
            }
        });


        // --- Functions ---

        /**
         * Fetches and displays the full content of an email in the modal.
         * @param {number} uid The UID of the email message.
         */
        function viewEmailContent(uid) {
            // Reset modal content and show loading state
            modalSubject.textContent = 'Loading...';
            modalFrom.textContent = '...';
            modalDate.textContent = '...';
            modalAttachments.innerHTML = '';
            modalAttachmentsContainer.classList.add('hidden');
            modalBodyContent.innerHTML = '<div class="text-center p-8"><span class="animate-spin h-8 w-8 rounded-full border-4 border-r-transparent border-blue-500 inline-block"></span><p class="mt-2 text-gray-500">Loading full message...</p></div>';
            emailModal.classList.remove('hidden');


            fetch(`IMAP.php?action=get_content&uid=${uid}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // 1. Update Headers
                        modalSubject.textContent = data.subject;
                        modalFrom.textContent = data.from;
                        modalDate.textContent = data.date;
                        
                        // 2. Handle Attachments
                        if (data.attachments && data.attachments.length > 0) {
                            data.attachments.forEach(att => {
                                const size = formatBytes(att.bytes);
                                const downloadUrl = `IMAP.php?action=download_attachment&uid=${uid}&part=${att.part_index}`;
                                
                                const linkHtml = `
                                    <a href="${downloadUrl}" download="${att.filename}" 
                                       class="attachment-link flex items-center bg-gray-100 text-gray-800 text-sm p-2 rounded-lg transition duration-200 hover:bg-blue-100 hover:text-blue-700 border border-gray-200"
                                       title="${att.filename} (${size})">
                                        
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13.5" />
                                        </svg>
                                        
                                        <span class="truncate max-w-[120px]">${att.filename}</span>
                                        <span class="ml-2 text-xs text-gray-500 flex-shrink-0">(${size})</span>
                                    </a>
                                `;
                                modalAttachments.insertAdjacentHTML('beforeend', linkHtml);
                            });
                            modalAttachmentsContainer.classList.remove('hidden');
                        } else {
                            modalAttachmentsContainer.classList.add('hidden');
                        }

                        // 3. Handle Body Content
                        const isHTML = /<[a-z][\s\S]*>/i.test(data.body);
                        
                        if (isHTML) {
                            modalBodyContent.classList.remove('email-body-plaintext'); 
                            modalBodyContent.innerHTML = data.body;
                        } else {
                            modalBodyContent.classList.add('email-body-plaintext');
                            modalBodyContent.textContent = data.body; 
                        }

                        // 4. Remove the email card from the list (since it's now 'seen')
                        const cardToRemove = document.querySelector(`[data-uid="${uid}"]`);
                        if (cardToRemove) {
                            cardToRemove.remove();
                            if (emailList.children.length === 0) {
                                noEmailsMessage.classList.remove('hidden');
                                statusText.textContent = `0 messages loaded`;
                            } else {
                                statusText.textContent = `${emailList.children.length} messages loaded`;
                            }
                        }

                    } else {
                        modalBodyContent.innerHTML = `<div class="text-red-600 font-medium">Error loading email:</div><p>${data.message}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching email content:', error);
                    modalBodyContent.innerHTML = '<div class="text-red-600 font-medium">Error:</div><p>Failed to load email content due to a network or server error.</p>';
                });
        }

        // Function to create and display email items
        function displayEmails(data) {
            emailList.innerHTML = '';
            if (data.emails && data.emails.length > 0) {
                data.emails.forEach(email => {
                    const emailCard = document.createElement('div');
                    
                    emailCard.className = 'email-item bg-white p-4 border border-gray-200 rounded-lg shadow-sm cursor-pointer transition-all duration-150 ease-in-out hover:shadow-md hover:border-blue-300';
                    emailCard.dataset.uid = email.uid; 
                    
                    const attachmentIcon = email.has_attachments ? 
                        `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13.5" /></svg>` 
                        : '';

                    emailCard.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div class="flex flex-col overflow-hidden pr-2">
                            <span class="text-sm font-bold text-gray-800 truncate">${email.subject}</span>
                            <div class="flex items-center mt-0.5">
                                ${attachmentIcon}
                                <span class="text-xs font-medium text-blue-600 truncate">From: ${email.from}</span>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-2 pt-1">
                              ${email.date || 'Today'} 
                            <span class="ml-2 inline-block h-2 w-2 bg-red-500 rounded-full animate-pulse" title="Unread"></span>
                        </span>
                    </div>
                    
                    <p class="text-sm text-gray-600 mt-2 truncate w-full pr-4">${email.body}</p>
                `;
                    
                    // --- ATTACH CLICK HANDLER ---
                    emailCard.addEventListener('click', (e) => {
                        e.preventDefault();
                        const uid = e.currentTarget.dataset.uid;
                        if (uid) {
                            viewEmailContent(uid);
                        }
                    });

                    emailList.appendChild(emailCard);
                });
                noEmailsMessage.classList.add('hidden');
            } else {
                noEmailsMessage.classList.remove('hidden');
            }
        }

        // Fetch emails from IMAP.php
        function fetchEmails() {
            loadingSpinner.classList.remove('hidden');
            statusText.textContent = 'Loading...';
            statusText.classList.remove('text-green-600', 'text-red-600');
            statusText.classList.add('text-indigo-600'); 


            fetch('IMAP.php?action=list') 
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    loadingSpinner.classList.add('hidden');

                    const count = data.emails ? data.emails.length : 0;
                    statusText.textContent = `${count} messages loaded`;
                    statusText.classList.remove('text-indigo-600', 'text-red-600');
                    statusText.classList.add('text-green-600'); 

                    displayEmails(data);
                })
                .catch(error => {
                    loadingSpinner.classList.add('hidden');
                    statusText.textContent = 'Connection Error';
                    statusText.classList.remove('text-indigo-600', 'text-green-600');
                    statusText.classList.add('text-red-600'); 
                    console.error('Error fetching emails:', error);

                    emailList.innerHTML = '';
                    noEmailsMessage.classList.remove('hidden');
                });
        }

        document.addEventListener('DOMContentLoaded', fetchEmails);
    </script>

</body>

</html>