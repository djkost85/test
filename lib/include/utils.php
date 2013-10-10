<?php

/**
 * Gets the specified HTTP Parameter string, SQL escaping it if the DB object is passed and also converting non standard quote characters if need too.
 *
 * @param string $param HTTP Parameter to get
 * @param string $default The default value to return if that parameter isn't defined
 * @param dbTools $db the DB object used to escape SQL characters - use this if the parameters value is passed through to SQL
 * @param boolean $convertQuotes whether to convert quotes to standard ascii values - eg ' and "
 * @return string The HTTP parameter value after processing
 */
function getParameterString($param, $default = "", $db = null, $convertQuotes = false) {
    if (isset($_REQUEST[$param]))
        if ($db != null)
            if ($convertQuotes) {
                return $db->escape(convertSpecialQuotes($_REQUEST[$param]));
            }
            else
                return $db->escape($_REQUEST[$param]);
        else
            return $_REQUEST[$param];
    else
    if ($db != null)
        return $db->escape($default);
    else
        return $default;
}

function getParameterNumber($param, $default = 0) {

    if (isset($_REQUEST[$param])) {
        $tmp = trim($_REQUEST[$param]);
        if (is_numeric($tmp))
            return $tmp;
    }

    return $default;
}

function getParameterArray($param, $db = null) {
    $result = Array();

    if (isset($_REQUEST[$param]) && is_array($_REQUEST[$param])) {
        foreach ($_REQUEST[$param] as $item) {
            if ($db != null) {
                array_push($result, $db->escape(convertSpecialQuotes($item)));
            } else {
                array_push($result, convertSpecialQuotes($item));
            }
        }
    }
    return $result;
}

function getPostParameterStringArray($db = null) {
    $cleanPostParams = array();

    if ($db != null) {
        foreach ($_POST as $name => $value) { // $_POST variables cleaned using $db->escape()
            $cleanPostParams [$db->escape($name)] = $db->escape(convertSpecialQuotes($value));
        }
    }

    return $cleanPostParams;
}

function checkParameter($param, $value = null) {
    if ($value == null) {
        if (isset($_REQUEST[$param]) && trim($_REQUEST[$param]) != '')
            return true;
    }else {
        if (isset($_REQUEST[$param]) && trim($_REQUEST[$param]) == $value)
            return true;
    }

    return false;
}

function getCookieString($name, $default=null) {
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
}

function getCookieNumber($name, $default=null) {
    return 0 + ((isset($_COOKIE[$name]) && is_numeric($_COOKIE[$name])) ? $_COOKIE[$name] : $default);
}

function getFileByName($filename, $db = null) {
    ParameterException::checkString($filename, "filename");
    foreach ($_FILES as $file) {
        if ($file['name'] == $filename) {
            $data = file_get_contents($file['tmp_name']);
        }
    }
    if (empty($data)) {
        throw new ParameterException("File not found in request: " . $filename);
    } else if (!empty($db)) {
        return $db->escape($data);
    } else {
        return $data;
    }
}

// this function converts the special quotes - left and right versions - to normal ones.
// These left and right versions often appear when cutting and pasting text from Word.
function convertSpecialQuotes($str) {
    // Trying a different approach, as byte conversion falls over when dealing with double byte characters.
    // this is a bit slower, but more reliable.
    $_encStr = urlencode($str);
    $_encStr = str_replace("%E2%80%98", "%27", $_encStr);
    $_encStr = str_replace("%E2%80%99", "%27", $_encStr);

    $_encStr = str_replace("%E2%80%9C", "%22", $_encStr);
    $_encStr = str_replace("%E2%80%9D", "%22", $_encStr);

    return urldecode($_encStr);
}

function drawOptionsFromArray($array, $selected) {
    foreach ($array as $value) {
        if ($selected == $value)
            $sel = 'selected="selected"';
        else
            $sel = "";

        echo "<option value=\"$value\" $sel>$value</option>\n";
    }
}

function drawOptionsFromHashtable($hash, $selected) {
    if ($hash != null) {
        foreach ($hash as $key => $value) {
            if ($selected == $key)
                $sel = 'selected="selected"';
            else
                $sel = "";

            echo "<option value=\"$key\" $sel>$value</option>\n";
        }
    }
}

function drawOptionsFromHashtableMultipleSelected($hash, $options_selected) {
    foreach ($hash as $key => $value) {
        foreach ($options_selected as $selected) {
            if ($selected == $key) {
                $sel = 'selected="selected"';
                break;
            } else {
                $sel = "";
            }
        }
        echo "<option value=\"$key\" $sel>$value</option>\n";
    }
}

function convertVideoTime($duration) {
    if ($duration > 3600)
        return sprintf("%02d:%02d:%02d", floor($duration / 3600), floor(($duration % 3600) / 60), $duration % 60);
    else
        return sprintf("%02d:%02d:%02d", 00, floor($duration / 60), $duration % 60);
}

function msort($array, $id) {
    $temp_array = array();
    while (count($array) > 0) {
        $lowest_id = 0;
        $index = 0;
        foreach ($array as $item) {
            if (isset($item[$id]) && $array[$lowest_id][$id]) {
                if ($item[$id] < $array[$lowest_id][$id]) {
                    $lowest_id = $index;
                }
            }
            $index++;
        }
        $temp_array[] = $array[$lowest_id];
        $array = array_merge(array_slice($array, 0, $lowest_id), array_slice($array, $lowest_id + 1));
    }
    return $temp_array;
}

// From yyyy-mm-dd to dd-mm-yyyy or dd-mm-yyyy to yyyy-mm-dd
function reverseDateFormat($date) {
    $date_arr = explode("-", $date);
    return $date_arr[2] . "-" . $date_arr[1] . "-" . $date_arr[0];
}

// From dd-mm-yyyy hh:mm to yyyy-mm-dd hh:mm:ss
function convertDateTimeFormat($dateTime) {
    list($date, $time) = explode(' ', $dateTime);
    $date_converted = reverseDateFormat($date);
    $time_converted = $time . ":00";
    return $date_converted . " " . $time_converted;
}

// Test if two dates in dd-mm-yyyy format span months
function ifSpanMonth($dateStart, $dateEnd) {
    $dateStart_arr = explode("-", $dateStart);
    $dateEnd_arr = explode("-", $dateEnd);
    if (($dateStart_arr[2] < $dateEnd_arr[2]) ||
            ( ($dateStart_arr[2] == $dateEnd_arr[2]) && ($dateStart_arr[1] < $dateEnd_arr[1]) )) {
        return true;
    }
}

if (!function_exists('cal_days_in_month')) {

    function cal_days_in_month($a_null, $a_month, $a_year) {
        return date('t', mktime(0, 0, 0, $a_month + 1, 0, $a_year));
    }

}

// convert from #xxxxxx to 0xyyyyyy
function colorHtmlToHex($htmlCode) {
    $red_value = substr($htmlCode, 1, 2);
    $green_value = substr($htmlCode, 3, 2);
    $blue_value = substr($htmlCode, 5, 2);

    $hex_value = "0x" . $blue_value . $green_value . $red_value;
    return $hex_value;
}

// convert from yyyyyy to #xxxxxx
function colorHexToHtml($hexValue) {
    $hexValueLength = strlen($hexValue);

    if ($hexValueLength == 5) {
        $hexValue = "0" . $hexValue;
    } else if ($hexValueLength == 4) {
        $hexValue = "00" . $hexValue;
    } else if ($hexValueLength == 3) {
        $hexValue = "000" . $hexValue;
    } else if ($hexValueLength == 2) {
        $hexValue = "0000" . $hexValue;
    } else if ($hexValueLength == 1) {
        $hexValue = "00000" . $hexValue;
    }

    $hexValue_0 = substr($hexValue, 0, 2);
    $hexValue_1 = substr($hexValue, 2, 2);
    $hexValue_2 = substr($hexValue, 4, 2);

    $html_value = "#" . $hexValue_2 . $hexValue_1 . $hexValue_0;
    return $html_value;
}

function byteConvert($bytes) {
    $s = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');
    $e = floor(log($bytes) / log(1024));

    if ($bytes > 0) {
        return sprintf('%.2f ' . $s[$e], ($bytes / pow(1024, floor($e))));
    } else {
        return 0;
    }
}

// Uses the OS stat utility to check if a file exist and is larger than 2GB
function file_exists_64($file) {
    $returnCode = 0;
    $output = array();
    @exec('stat -c %s ' . escapeshellarg($file) . " 2>&1", $output, $returnCode);
    if ($returnCode > 0)
        return false;
    return true;
}

// Uses the OS stat utility to check a file's size (support for files larger than 2GB)
function filesize_64($file) {
    $returnCode = 0;
    $output = array();
    @exec('stat -c %s ' . escapeshellarg($file) . " 2>&1", $output, $returnCode);
    if ($returnCode > 0)
        return false;
    return $output;
}

/**
 * Dynamic helper for cURL handle creation.
 * @param string $url The URL we are communicating with.
 * @param string $data Associated data for this request. It has several uses:
 *                      @li For GET, just pass a blank string
 *                      @li For POST, you can use postdata
 *                          (in query string format) OR a file path
 *                      @li For PUT, provide an absolute file path
 * @param string $method The method to use. Defaults to GET. Support for GET/POST/PUT.
 * @param array $headers Optional HTTP headers to set on the request.
 * @return The cURL handle (call @c curl_exec($handle) to use it).
 */
function _curl($url, $data, $method = 'GET', $headers = null) {
    $handle = @curl_init($url);

    switch ($method) {
        default:
        case 'GET':
            break;

        case 'POST':
            @curl_setopt($handle, CURLOPT_POST, true);

            if (file_exists_64($data)) {
                @curl_setopt($handle, CURLOPT_POSTFIELDS, "@$data");
                @curl_setopt($handle, CURLOPT_UPLOAD, true);
            } else {
                @curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
            }

            break;

        case 'PUT':
            @curl_setopt($handle, CURLOPT_PUT, true);
            @curl_setopt($handle, CURLOPT_UPLOAD, true);
            @curl_setopt($handle, CURLOPT_INFILE, @fopen($data, 'rb'));
            @curl_setopt($handle, CURLOPT_INFILESIZE, filesize_64($data));

        case 'FTP':
            break;
    }

    if (is_array($headers)) {
        @curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    }

    @curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
    @curl_setopt($handle, CURLOPT_MAXREDIRS, 10);
    @curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    return $handle;
}

/**
 * High-level function to encapsulate the creation and execution of a cURL HTTP request.
 * @param string $url The URL we are communicating with.
 * @param string $data Associated data for this request. It has several uses:
 *                      @li For GET, just pass a blank string
 *                      @li For POST, you can use postdata
 *                          (in query string format) OR absolute file path
 *                      @li For PUT, provide an absolute file path
 * @param string $method The method to use. Defaults to GET. Support for GET/POST/PUT.
 * @param array $headers Optional HTTP headers to set on the request.
 * @param integer &$status A reference to a variable, which will be set to the
 *                         HTTP status code of the response.
 * @param integer &$errorCode A reference to a variable, which will be set to
 *                            the cURL status code returned from the connection.
 * @return The response string.
 */
function curl($url, $data, $method = 'GET', $headers = null, &$status = null, &$errorCode = null, &$errorMsg = null) {
    $handle = _curl($url, $data, $method, $headers);
    $response = @curl_exec($handle);

    $info = curl_getinfo($handle);
    $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);   // allow SSL but ignore certificate errors
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    $errorCode = curl_errno($handle);
    $errorMsg = curl_error($handle);
    curl_close($handle);

    return $response;
}

/**
 * High-level function to encapsulate the creation and execution of a cURL SSL request.
 * @param string $url The URL we are communicating with.
 * @param string $data Associated data for this request. It has several uses:
 *                      @li For GET, just pass a blank string
 *                      @li For POST, you can use postdata
 *                          (in query string format) OR absolute file path
 *                      @li For PUT, provide an absolute file path
 * @param string $cert The path to an additional Certificate Authority file
 *                     (used to validate the host's certificate).
 * @param string $method The method to use. Defaults to GET. Support for GET/POST/PUT.
 * @param array $headers Optional HTTP headers to set on the request.
 * @param integer &$status A reference to a variable, which will be set to the
 *                         HTTP status code of the response.
 * @param integer &$errorCode A reference to a variable, which will be set to
 *                            the cURL status code returned from the connection.
 * @return The response string.
 */
function curlSSL($url, $data, $cert = null, $method = 'GET', $headers = null, &$status = null, &$errorCode = null) {
    $handle = _curl($url, $data, $method, $headers);
    //@curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
    @curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

    if ($cert !== null) {
        @curl_setopt($handle, CURLOPT_CAINFO, $cert);
    }

    $response = @curl_exec($handle);

    $info = curl_getinfo($handle);
    $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $errorCode = curl_errno($handle);
    curl_close($handle);

    return $response;
}

/**
 * High-level function to encapsulate the creation and execution of a cURL FTP request.
 * @param string $url The URL we are communicating with.
 * @param string $data Associated data for this request.
 *                     For FTP, this can be a file if you wish to upload one.
 * @param string $username The FTP username.
 * @param string $password The FTP password.
 * @param boolean $listFiles The type of action we are performing.
 *                           (true = directory listing, ignoring $data;
 *                            false = uploading or downloading a file [default])
 * @param integer &$status A reference to a variable, which will be set to the
 *                         HTTP status code of the response.
 * @param integer &$errorCode A reference to a variable, which will be set to
 *                            the cURL status code returned from the connection.
 * @return The response string.
 */
function curlFTP($url, $data, $username, $password, $listFiles = false, &$status = null, &$errorCode = null) {
    $handle = _curl($url, $data, 'FTP');
    @curl_setopt($handle, CURLOPT_USERPWD, "$username:$password");

    if ($listFiles)
        @curl_setopt($handle, CURLOPT_FTPLISTONLY, true);
    else if ($data !== null) {
        @curl_setopt($handle, CURLOPT_UPLOAD, true);
        @curl_setopt($handle, CURLOPT_INFILE, @fopen($data, 'rb'));
//        @curl_setopt($handle, CURLOPT_INFILESIZE, filesize_64($data));
    }


    $response = @curl_exec($handle);

    $info = curl_getinfo($handle);
    $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $errorCode = curl_errno($handle);
    curl_close($handle);

    return $response;
}

/**
 * Global debug message filter, applied based on the value of the _debug parameter.
 * @param string $message The debug message to print (if debug mode is on).
 */
function debug_echo($message) {
    if (getParameterNumber("_debug", 0))
        echo $message;
}

/**
 * Global debug logging filter, applied based on the value of the _debug parameter.
 * @param string $message The debug message to log (if debug mode is on).
 */
function debug_error_log($message) {
    if (getParameterNumber("_debug", 0))
        error_log($message);
}

/**
 * Function to convert seconds to a human-readable time.
 * 
 * @param type $seconds
 * @return integer
 */
function seconds_to_h($seconds, $shorthand = false) {
    $divisors = array(
        (365 * 24 * 60 * 60),
        (7 * 24 * 60 * 60),
        (24 * 60 * 60),
        (60 * 60),
        (60),
        (1)
    );

    $timeString = "";

    $years = intval($seconds / $divisors[0]);
    $seconds %= $divisors[0];
    $label = ($shorthand ? "Y:" : "years, ");
    $timeString .= ($years > 0 ? "$years$label" : "");

    $weeks = intval($seconds / $divisors[1]);
    $seconds %= $divisors[1];
    $label = ($shorthand ? "W:" : "weeks, ");
    $timeString .= ($weeks > 0 ? "$weeks$label" : "");

    $days = intval($seconds / $divisors[2]);
    $seconds %= $divisors[2];
    $label = ($shorthand ? "D:" : "days, ");
    $timeString .= ($days > 0 ? "$days$label" : "");

    $hours = intval($seconds / $divisors[3]);
    $seconds %= $divisors[3];
    $label = ($shorthand ? "h:" : "hours, ");
    $timeString .= ($hours > 0 ? "$hours$label" : "");

    $minutes = intval($seconds / $divisors[4]);
    $seconds %= $divisors[4];
    $label = ($shorthand ? "m:" : "minutes, ");
    $timeString .= ($minutes > 0 ? "$minutes$label" : "");

    $label = ($shorthand ? "s" : "seconds");
    $timeString .= "$seconds$label";

    return $timeString;
}

?>
