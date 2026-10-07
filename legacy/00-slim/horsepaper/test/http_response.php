<?php
$url="https://racing.hkjc.com/racing/information/Chinese/Racing/LocalResults.aspx?RaceDate=2020/03/11&Racecourse=HV&RaceNo=1";
$url="https://racing.hkjc.com/racing/information/chinese/Horse/BTResult.aspx?Date=2020/03/19";
$is_ok = http_response($url); // returns true only if http response code < 400
?>

The second argument is optional, and it allows you to check for  a specific response code

<?php
//http_response($url,'400'); // returns true if http status is 400
?>

The third allows you to specify how long you are willing to wait for a response.

<?php
//http_response($url,'200',3); // returns true if the response takes less than 3 seconds and the response code is 200
?>

<?php
function http_response($url, $status = null, $wait = 3){
        $time = microtime(true);
        $expire = $time + $wait;

        // we fork the process so we don't have to wait for a timeout
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            // we are the parent
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $head = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
           
            if(!$head)
            {
                return FALSE;
            }
           
            if($status === null)
            {
                if($httpCode < 400)
                {
                    return TRUE;
                }
                else
                {
                    return FALSE;
                }
            }
            elseif($status == $httpCode)
            {
                return TRUE;
            }
           
            return FALSE;
            pcntl_wait($status); //Protect against Zombie children
        } else {
            // we are the child
            while(microtime(true) < $expire)
            {
            sleep(0.5);
            }
            return FALSE;
        }
    }
?>