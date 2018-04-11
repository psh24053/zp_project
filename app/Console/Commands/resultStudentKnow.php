<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class resultStudentKnow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resultStudentKnow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        ini_set('memory_limit', -1);
        set_time_limit(0);

        $exam = DB::table("gk_exam")
            ->where('id', '=', 20)
            ->first();

        if (empty($exam)){
            dump("exam is error!");
            return;
        }
        $exam_db = $exam->exam_db;

        $branchSubjects = DB::select("SELECT branch_id,subject_id
                FROM ${exam_db}.gk_exam_results
                GROUP BY branch_id,subject_id");

        $startTime = time();
        // 大约150秒
        foreach ($branchSubjects as $index => $item) {

            dump("run $index item: " . json_encode($item));

            DB::insert("INSERT INTO ${exam_db}.gk_result_student_know (exam_id,
                                       branch_id,
                                       subject_id,
                                       student_id,
                                       student_code,
                                       know_id,
                                       score)

                SELECT er.exam_id,
                  er.branch_id,
                  er.subject_id,
                  er.student_id,
                  er.student_code,
                  ek.know_id,
                  sum(round(er.score / r.know_count, 2)) as score
                FROM ${exam_db}.gk_exam_results er
                  JOIN ${exam_db}.gk_exam_know ek ON ek.scoring_point_id = er.scoring_point_id
                  JOIN (SELECT
                          scoring_point_id,
                          count(know_id) as know_count
                        FROM ${exam_db}.gk_exam_know
                        GROUP BY scoring_point_id) as r 
                        on r.scoring_point_id = er.scoring_point_id
                WHERE er.subject_id = ? and er.branch_id = ?
                GROUP BY er.branch_id,er.subject_id,er.student_code,ek.know_id
                ORDER BY er.branch_id,er.subject_id,er.student_code,ek.know_id",
                [$item->subject_id, $item->branch_id]);

            dump('use time: '. (time() - $startTime) . 's');
        }

        dump('over');

    }
}
