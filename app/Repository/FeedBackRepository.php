<?php

namespace DTApi\Repository;

use DTApi\Models\Job;
use DTApi\Models\Feedback;

/**
 * Class FeedBackRepository
 * @package DTApi\Repository
 */
class FeedBackRepository extends BaseRepository
{

    protected $model;

    /**
     * @param Feedback $model
     */
    function __construct(Feedback $model)
    {
        parent::__construct($model);
    }

    public function makeFeedBack($post_data, $user)
    {
        if (isset($post_data['ratings']) && ($post_data['ratings'] > 3 || ($post_data['ratings'] < 3 && $post_data['description'] != ''))) {
            $response['status'] = 'success';
        } elseif(!isset($post_data['ratings'])) {
            $response['status'] = 'fail';
            $response['message'] = 'Du måste även ge ett betyg på 1-5 stjärnor.';
            return $response;
        }
        elseif($post_data['ratings'] <= 3 && $post_data['description'] == '') {
            $response['status'] = 'fail';
            $response['message'] = 'Vi är ledsna att ni haft en dålig upplevelse. Vi skulle uppskatta om ni kunde ge en kort beskrivning om vad som gick fel på tolkningen så ska vi göra vårt bästa att säkerställa att det inte sker igen';
            return $response;
        }
        $data = array(
            'user_id' => $post_data['user_id'],
            'by_user_id' => $user->id,
            'job_id' => $post_data['job_id'],
            'rating' => $post_data['ratings'],
            'description' => $post_data['description'],
        );
        Feedback::create($data);
        $job = Job::find($post_data['job_id']);
        if ($job->customer_phone_type == 'yes' && ($job->customer_physical_type == 'no' || $job->customer_physical_type == '')) {
            $response['job_type'] = "phone";
        } else if (($job->customer_phone_type == 'no' || $job->customer_phone_type == '') && $job->customer_physical_type == 'yes') {
            $response['job_type'] = "plan";
        } else if ($job->customer_phone_type == 'yes' && $job->customer_physical_type == 'yes') {
            $response['job_type'] = "both";
        }
        $response['job_id'] = $post_data['job_id'];
        return $response;
    }

}