<?php

namespace DTApi\Helpers;
use DTApi\Models\Job;
use Exception;

/**
 * Class SendSMSHelper
 * @package DTApi\Helpers
 */
class SendSMSHelper
{

    /**
     * Send SMS
     * @param $from
     * @param $to
     * @param $message
     * @return string
     */
    public static function send($from, $to, $message)
    {
        $sms = [
            'from' => $from,
            'to' => $to,
            'message' => $message,
        ];

        $username = env('SMS_API_USERNAME');
        $password = env('SMS_API_PASSWORD');

//        $context = stream_context_create([
//            'http' => [
//                'method' => 'POST',
//                'header' => 'Authorization: Basic '
//                    . base64_encode( $username . ':' . $password) . "\r\n"
//                    . "Content-type: application/x-www-form-urlencoded\r\n",
//                'content' => http_build_query($sms),
//                'timeout' => 10,
//            ]]);
//        $response = file_get_contents("https://api.46elks.com/a1/SMS", false, $context);
//
//        if (!strstr($http_response_header[0], '200 OK'))
//            return $http_response_header[0];

        $ch = curl_init();
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '. base64_encode($username . ':' . $password)
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL,"https://api.46elks.com/a1/SMS");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($sms));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);

        \Log::info($server_output);

        if (!!$server_output) {
            return $server_output;
        } else {
            throw new Exception('Failed to contact SMS server');
        }


    }

    /**
     * Determine weather a job is phone or physical
     * @param \DTApi\Models\Job $job
     * @return string
     */
    public static function determineJobType(Job $job)
    {
        // Analyse weather it's phone or physical; if both = default to phone
        if ($job->customer_physical_type == 'yes' && $job->customer_phone_type == 'no') {
            // It's a physical job
            $jobType = 'physical';
        } else if ($job->customer_physical_type == 'no' && $job->customer_phone_type == 'yes') {
            // It's a phone job
            $jobType = 'phone';
        } else if ($job->customer_physical_type == 'yes' && $job->customer_phone_type == 'yes') {
            // It's both, but should be handled as phone job
            $jobType = 'phone';
        } else {
            // This shouldn't be feasible, so no handling of this edge case
            $jobType = '';
        }

        return $jobType;
    }

}