<?php
// -----------------------------------------------------------------------------
// 1. PHP IMAP Extension: Make sure the 'imap' extension is enabled in your
//    php.ini file.
// 2. App Password: Use a generated App Password for services like Gmail.
// -----------------------------------------------------------------------------

// Added /novalidate-cert to help with self-signed or older certs
define('IMAP_SERVER', '{imap.gmail.com:993/imap/ssl/novalidate-cert}');
define('EMAIL_ADDRESS', 'sintadriveph@gmail.com');
define('PASSWORD', 'hbnx prwb ijec ukmf'); // <-- App Password from Gmail

// Set content type to JSON by default, but this will be overridden for file downloads
header('Content-Type: application/json');

/**
 * Decodes MIME-encoded header strings.
 */
function decodeMimeHeader($header) {
    $elements = imap_mime_header_decode($header);
    $decoded_string = '';
    foreach ($elements as $element) {
        $decoded_string .= $element->text;
    }
    return $decoded_string;
}

/**
 * Decodes encoded content based on encoding type.
 */
function decodeContent($data, $encoding) {
    if ($encoding == 1) { // 8BIT
        // Do nothing
    } elseif ($encoding == 2) { // 7BIT
        // Do nothing
    } elseif ($encoding == 3) { // BASE64
        $data = base64_decode($data);
    } elseif ($encoding == 4) { // QUOTED-PRINTABLE
        $data = quoted_printable_decode($data);
    }
    // Type 5 (Other) and 0 (7bit) also require no decoding here
    return $data;
}

/**
 * Recursively fetches and decodes the content of a specific part.
 */
function getPartContent($inbox, $msg_number, $part_number_imap, $part) {
    // Note: imap_fetchbody already safely handles if the body is empty or non-existent for the given part index.
    $data = imap_fetchbody($inbox, $msg_number, $part_number_imap);
    return decodeContent($data, $part->encoding);
}

/**
 * Recursively traverses the email structure to extract body content and attachments.
 */
function parseStructure($inbox, $msg_number, $structure, $path = "") {
    $results = ['body' => null, 'attachments' => []];
    $parts = $structure->parts ?? [$structure]; // Treat single-part as an array of one

    foreach ($parts as $index => $part) {
        // Construct the IMAP path for this part
        $part_path = $path . ($path ? '.' : '') . ($index + 1);

        // Check for nested parts (e.g., multipart/alternative)
        if (isset($part->parts)) {
            $nested_results = parseStructure($inbox, $msg_number, $part, $part_path);
            
            // Merge body content with safer access
            $current_body_type = $results['body']['type'] ?? '';
            $nested_body = $nested_results['body'] ?? null;
            $nested_body_type = $nested_body['type'] ?? '';

            if ($nested_body) {
                // If we don't have a body yet, OR the nested part is plain text and our current part is not plain text (we prefer plain text over HTML for simple display)
                if (!$results['body'] || ($nested_body_type === 'plain' && $current_body_type !== 'plain')) {
                    $results['body'] = $nested_body;
                }
            }
            $results['attachments'] = array_merge($results['attachments'], $nested_results['attachments']);
        } 
        
        // Check for body content (Type 0 - Text)
        elseif ($part->type == 0) {
            $content = getPartContent($inbox, $msg_number, $part_path, $part);
            $type = strtolower($part->subtype);
            
            // Safer check: Prioritize plain text over HTML for clean display in the snippet
            $current_body_type = $results['body']['type'] ?? '';

            if ($type == 'plain' && $current_body_type != 'plain') {
                $results['body'] = ['type' => 'plain', 'content' => $content];
            } elseif ($type == 'html' && !$results['body']) {
                $results['body'] = ['type' => 'html', 'content' => $content];
            }
        }
        
        // Check for attachments (Disposition ATTACHMENT, or non-text content types)
        // Skip inline images and body content types (0, 1)
        elseif (!in_array($part->type, [0, 1])) { 
            $filename = '';
            
            // Safely check content disposition parameters
            if (isset($part->dparameters)) {
                foreach ($part->dparameters as $param) {
                    if (strtolower($param->attribute) === 'filename') {
                        $filename = decodeMimeHeader($param->value);
                        break;
                    }
                }
            }
            
            // Safely check header parameters (Content-Type name)
            if (empty($filename) && isset($part->parameters)) {
                foreach ($part->parameters as $param) {
                    if (strtolower($param->attribute) === 'name') {
                        $filename = decodeMimeHeader($param->value);
                        break;
                    }
                }
            }
            
            if (!empty($filename)) {
                $results['attachments'][] = [
                    'filename' => $filename,
                    'part_index' => $part_path, // The part number needed for fetching
                    'mime_type' => getMimeType($part),
                    'bytes' => $part->bytes ?? 0,
                    'is_attachment' => (isset($part->disposition) && strtolower($part->disposition) == 'attachment') || $part->type > 1 // Heuristic
                ];
            }
        }
    }
    return $results;
}


/**
 * Determines the MIME type of a part based on its type and subtype.
 */
function getMimeType($part) {
    $primary_types = [
        0 => "text",
        1 => "multipart",
        2 => "message",
        3 => "application",
        4 => "audio",
        5 => "image",
        6 => "video",
        7 => "other"
    ];
    $primary_type = $primary_types[$part->type] ?? "other";
    $subtype = strtolower($part->subtype ?? "unknown");
    return $primary_type . "/" . $subtype;
}

/**
 * Handles email requests (list, detail, and download).
 */
function processEmailRequest() {
    $response = [
        'status' => 'error',
        'message' => 'Initialization error.',
    ];
    
    $inbox = @imap_open(IMAP_SERVER . 'INBOX', EMAIL_ADDRESS, PASSWORD);

    if (!$inbox) {
        $response['message'] = 'IMAP Connection Error. Check credentials/App Password: ' . imap_last_error();
        echo json_encode($response);
        return;
    }

    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'download_attachment' && isset($_GET['uid']) && isset($_GET['part'])) {
        // --- Action: Download Attachment ---
        $uid = intval($_GET['uid']);
        $part_index = $_GET['part'];
        $msg_number = imap_msgno($inbox, $uid);

        if (!$msg_number) {
            header("HTTP/1.1 404 Not Found");
            echo "Error: Invalid email UID or message not found.";
            imap_close($inbox);
            return;
        }

        $structure = imap_fetchstructure($inbox, $msg_number);
        $part = $structure;
        $parts = explode('.', $part_index);
        
        // Navigate through the structure to find the correct part object
        foreach ($parts as $p) {
            // Safely access the part index
            if (isset($part->parts[intval($p) - 1])) {
                $part = $part->parts[intval($p) - 1];
            } else {
                header("HTTP/1.1 404 Not Found");
                echo "Error: Invalid part index.";
                imap_close($inbox);
                return;
            }
        }
        
        // Extract filename and mime type for headers
        $filename = 'attachment';
        
        // Use dparameters (Content-Disposition) first
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) === 'filename') {
                    $filename = decodeMimeHeader($param->value);
                    break;
                }
            }
        }
        // Fallback to parameters (Content-Type name)
        if ($filename === 'attachment' && isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) === 'name') {
                    $filename = decodeMimeHeader($param->value);
                    break;
                }
            }
        }
        
        $mime_type = getMimeType($part);

        // Fetch and decode the attachment content
        $data = imap_fetchbody($inbox, $msg_number, $part_index);
        $content = decodeContent($data, $part->encoding);

        // Set headers for download
        header('Content-Type: ' . $mime_type);
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));
        
        echo $content;
        
        imap_close($inbox);
        return; // Terminate script after file download
        
    } elseif ($action === 'get_content' && isset($_GET['uid'])) {
        // --- Action: Get Full Email Content and Attachments ---
        $uid = intval($_GET['uid']);
        $msg_number = imap_msgno($inbox, $uid);

        if (!$msg_number) {
            $response['message'] = 'Invalid email UID or message not found.';
            imap_close($inbox);
            echo json_encode($response);
            return;
        }

        $header = imap_headerinfo($inbox, $msg_number);
        $structure = imap_fetchstructure($inbox, $msg_number);
        
        $parsed = parseStructure($inbox, $msg_number, $structure);
        $full_body = $parsed['body']['content'] ?? 'No readable content found.';
        $attachments = $parsed['attachments'];
        
        // Mark the email as seen after reading the content
        imap_setflag_full($inbox, $msg_number, "\\Seen");
        
        $response = [
            'status' => 'success',
            'uid' => $uid,
            'subject' => decodeMimeHeader($header->subject ?? '(No Subject)'),
            'from' => decodeMimeHeader($header->fromaddress ?? '(Unknown Sender)'),
            'date' => date('M d, Y H:i:s', strtotime($header->date)),
            'body' => $full_body,
            'attachments' => $attachments // Include attachment metadata
        ];
        
    } else {
        // --- Action: List Unseen Emails (Default) ---
        $response['emails'] = [];
        $emails = imap_search($inbox, 'UNSEEN');
        
        if ($emails) {
            $response['status'] = 'success';
            $response['message'] = 'Found ' . count($emails) . ' new email(s).';

            $emails = array_slice($emails, 0, 20); 

            foreach ($emails as $msg_number) {
                $uid = imap_uid($inbox, $msg_number); 
                $header = imap_headerinfo($inbox, $msg_number);
                $subject = decodeMimeHeader($header->subject ?? '(No Subject)');
                $from = decodeMimeHeader($header->fromaddress ?? '(Unknown Sender)');
                $date = date('M d, Y', strtotime($header->date));
                
                // Fetch the body structure for snippet and attachment check
                $structure = imap_fetchstructure($inbox, $msg_number);
                $parsed = parseStructure($inbox, $msg_number, $structure);
                
                // Safely extract body content for snippet
                $body_snippet = strip_tags($parsed['body']['content'] ?? 'No preview available.');
                
                $response['emails'][] = [
                    'uid' => $uid,
                    'from' => $from,
                    'subject' => $subject,
                    'date' => $date,
                    'body' => substr($body_snippet, 0, 200) . "...",
                    'has_attachments' => !empty($parsed['attachments'])
                ];
            }
        } else {
            $response['status'] = 'success';
            $response['message'] = 'No new unseen emails found.';
        }
    }

    imap_close($inbox);
    echo json_encode($response);
}

processEmailRequest();
?>
