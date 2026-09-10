<?php

return [
    /*
     | Days after a trial ends before the account is actually shut off.
     |
     | Expiry used to mean deactivation the same morning. For a realtor that is their public
     | listing site going dark at 8am, decided by a cron with nobody in the loop — and the
     | cost of a few extra free days is nothing beside the cost of doing that to someone who
     | simply had not got round to entering a card.
     |
     | The window is honoured by the account gate as well as by the command, so grace means
     | the site keeps working rather than the shutdown merely being recorded later.
     */
    'trial_grace_days' => (int) env('BILLING_TRIAL_GRACE_DAYS', 3),
];
