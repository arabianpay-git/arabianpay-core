<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseSyncController extends Controller
{
    public function sync()
    {
        header('Content-Type: text/plain');
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', false);
        @ini_set('implicit_flush', true);
        ob_implicit_flush(true);

        echo "🚀 Starting backup sync...\n";
        flush();

        $filename = 'backup_' . now()->format('Y_m_d_His') . '.sql.gz';
        $localPath = "/tmp/$filename";

        $db = config('database.connections.mysql');

        $remoteUser = 'ubuntu';
        $remoteHost = '79.72.9.45';
        $remotePath = "/tmp/$filename";
        $remoteDbUser = 'root';
        $remoteDbPass = 'Admin@123';
        $remoteDbName = 'arabianpay';

        try {
            // Step 1: Dump and compress DB
            echo "📦 Dumping and compressing local database...\n";
            flush();
            $dumpCmd = "mysqldump -u {$db['username']} -p'{$db['password']}' -h {$db['host']} {$db['database']} | gzip > $localPath";
            $this->runAndStream($dumpCmd);

            // Step 2: SCP to remote
            echo "📤 Copying to remote server $remoteHost...\n";
            flush();
            $scpCmd = "scp $localPath $remoteUser@$remoteHost:$remotePath";
            $this->runAndStream($scpCmd);

            // Step 3: SSH import on remote
            echo "🛠 Importing into remote database 'arabianpay'...\n";
            flush();
            $sshCmd = "ssh $remoteUser@$remoteHost \"gunzip -c $remotePath | mysql -u $remoteDbUser -p'$remoteDbPass' $remoteDbName\"";
            $this->runAndStream($sshCmd);

            echo "✅ Sync complete.\n";
            flush();
        } catch (ProcessFailedException $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            flush();
        }

        echo "\nDone.";
    }

    protected function runAndStream(string $command)
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            echo "  " . trim($buffer) . "\n";
            flush();
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
