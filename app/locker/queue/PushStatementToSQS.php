<?php namespace app\locker\queue;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Facades\Log;

class PushStatementToSQS {
    public function fire($job, $data)
    {
        $lrs = \Lrs::find($data['lrs_id'])->first();

        if (!$lrs) {
            $job->delete();
        } else if (empty($lrs->awsaccesskey) || empty($lrs->awssecretkey) || empty($lrs->awssqsarn)) {
            $job->delete();
        }

        try {
            // TODO: Parse the ARN safely.
            $arnparts = explode(':', $lrs->awssqsarn);
            $region = $arnparts[3];
            $queue = $arnparts[5];

            // TODO: Factory this out so we don't instantiate it over and over again?
            $sqs = new SqsClient(array(
                'credentials' => array(
                    'key' => $lrs->awsaccesskey,
                    'secret' => $lrs->awssecretkey
                ),
                'region' => $region,
                'version' => 'latest',
            ));

            // TODO: Cache queue URLs.
            $result = $sqs->getQueueUrl(array('QueueName' => $queue));
            $queueurl = $result->get('QueueUrl');

            $sqs->sendMessage(array(
                'QueueUrl' => $queueurl,
                'MessageBody' => $data['statement']
            ));
        } catch (Exception $ex) {
            Log::error($ex);

            $job->release();
        }
    }
}