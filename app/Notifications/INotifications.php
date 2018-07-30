<?php

namespace DTApi\Notifications;


interface INotifications
{
    /*
     * cron for session start
     */
    public function sessionStart();

    /*
     * Booking immidiate not accepted
     */
    public function bookingNotAccepted();

    /*
     * Booking within 24 hours: Suppliers/translators have 90minutes to respond - if not, we email customer informing we haven’t managed to arrange a customer.
     */
    public function bookingWithing24h();

    /*
     * If no translator has accepted after 45 minutes - an email is sent to virpal@digitaltolk.se informing that a booking has been placed but not accepted by any supplier.
     */
    public function bookingNotAccepted45m();

    /*
     * Booking after 24 hours: Suppliers/translators have 16 hours to respond - if not, we email customer informing we haven’t managed to arrange a customer.
     */
    public function bookingAfter24h();

    /*
     * If no translator has accepted after 6 hours - an email is sent to virpal@digitaltolk.se informing that a booking has been placed but not accepted by any supplier.
     */
    public function bookingNotAcceptedAfter6h();

    /*
     * end session after 8 hours
     */
    public function endSessionAfter8h();

    /*
     * START 16 HOUR EMAIL
     */
    public function emailAfter16h();

    /*
     * START 48 HOUR EMAIL
     */
    public function emailBefore48h();

    /*
    * Code for check 4 hours after booking time 24-72 and due-60 hours for 72+ to send push notification to translators again
    */
    public function due60h();

    /**
     *  this function is for checking the session starting time, if yes send Push to Translator/Customer to remind them for starting session
     */
    public function checkingSessionStartRemindTime();

    public function sendNotificationMailToTranslator($translator, $job);

    /*
     * this function is for checking the session ending time came, if yes send Push to Translator/Customer to reminde them for finishing session
     */
    public function checkingSessionEndRemind();

    /*
     * send session start remind notification
     */
    public function sendSessionStartRemindNotification($user, $job, $language, $due, $duration);

    /*
     * send session end remind notification
     */
    public function sendSessionEndRemindNotification($user, $job_id);

    public function reminderToAddDuration();

    public function sendPushToTranslators($time_start, $time_end, $time_for_push);

    public function checkExpiringBookings($time_start, $time_end, $time_for_push, $email_template = 'job-not-acceptednew');

}