<?php
/**
 * Database Connection Configuration
 * For Hostinger MySQL Hosting
 */

// Database Configuration
define('DB_HOST', 'localhost'); // Change to your Hostinger database host
define('DB_USER', 'root'); // Your database username
define('DB_PASS', '1234'); // Your database password
define('DB_NAME', 'readers_store'); // Your database name
define('DatabasePort', 3300); 

// For Hostinger, typically use:
// DB_HOST: Usually 'localhost' or provided by Hostinger
// DB_USER: Your cPanel username or dedicated database user
// DB_PASS: Your database password
// DB_NAME: Your database name

// Error Reporting
define('DEBUG_MODE', true); // Set to false in production

// Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DatabasePort);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die('Connection Error: ' . $e->getMessage());
    } else {
        die('Database connection error. Please try again later.');
    }
}

/**
 * Helper function to execute queries and return results
 */
function executeQuery($query, $params = []) {
    global $conn;
    
    try {
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            
            // Bind parameters
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_float($param)) $types .= 'd';
                elseif (is_bool($param)) $types .= 'i';
                else $types .= 's';
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            return $stmt;
        } else {
            return $conn->query($query);
        }
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log('Query Error: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Helper function to get a single row as associative array
 */
function getRow($query, $params = []) {
    global $conn;
    
    if (!empty($params)) {
        // Prepared statement
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log('Prepare failed: ' . $conn->error);
            return null;
        }
        
        // Bind parameters
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            elseif (is_bool($param)) $types .= 'i';
            else $types .= 's';
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row;
        }
        $stmt->close();
        return null;
    } else {
        // Regular query
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
}

/**
 * Helper function to get all rows as associative array
 */
function getRows($query, $params = []) {
    global $conn;
    $rows = [];
    
    if (!empty($params)) {
        // Prepared statement
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log('Prepare failed: ' . $conn->error);
            return $rows;
        }
        
        // Bind parameters
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            elseif (is_bool($param)) $types .= 'i';
            else $types .= 's';
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    } else {
        // Regular query
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
    }
    
    return $rows;
}

/**
 * Helper function to get last inserted ID
 */
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

/**
 * Helper function to get affected rows
 */
function getAffectedRows() {
    global $conn;
    return $conn->affected_rows;
}

/**
 * Helper function to escape string
 */
function escape($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

?>
