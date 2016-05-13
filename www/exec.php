<?php
echo "START-";
//$output = shell_exec("(echo -e 'stats\n\n\n'; sleep 0.1) | nc 127.0.0.1 12010 2>&1 1> /dev/null");
//$output = shell_exec("nc 127.0.0.1 12010");
//shell_exec("echo 'stats\n\n\n' | nc -q -1 127.0.0.1 12001");
//echo $output;
//echo "-STOP";
$result = liveExecuteCommand('echo "stats\n\n\n" | nc -q -1 127.0.0.1 12001');

if($result['exit_status'] === "0"){
   // do something if command execution succeeds
    echo $result['output'];	
} else {
    // do something on failure
}


function liveExecuteCommand($cmd)
{

    while (@ ob_end_flush()); // end all output buffers if any

    $proc = popen("$cmd 2>&1 ; echo Exit status : $?", 'r');

    $live_output     = "";
    $complete_output = "";

    while (!feof($proc))
    {
        $live_output     = fread($proc, 4096);
        $complete_output = $complete_output . $live_output;
        echo "$live_output";
        @ flush();
    }

    pclose($proc);

    // get exit status
    preg_match('/[0-9]+$/', $complete_output, $matches);

    // return exit status and intended output
    return array (
                    'exit_status'  => $matches[0],
                    'output'       => str_replace("Exit status : " . $matches[0], '', $complete_output)
                 );
}
?>
