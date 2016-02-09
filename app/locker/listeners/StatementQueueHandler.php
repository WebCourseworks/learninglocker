<?php namespace app\locker\listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class StatementQueueHandler {

    public function handle($data){
        $statement = $data->statement;
        $opts = $data->opts;
        $lrsid = $opts->getOpt('lrs_id');
        $statementid = $statement->id;

        Log::debug(sprintf('Enqueuing statement %s in LRS %s for transmission to SQS.', $statementid, $lrsid));

        // We're going to defer the push to SQS here.  Every web server has a local redis instance to push these jobs
        // to, keeping the job and data local to the web server.  Latency and failure will be absorbed in the worker
        // and not inline in the xAPI request.
        Queue::push('app\locker\queue\PushStatementToSQS', array('statement' => json_encode($statement), 'lrs_id' => $lrsid));
    }
}