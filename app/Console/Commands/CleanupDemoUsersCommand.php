<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Console\Command;

class CleanupDemoUsersCommand extends Command
{
    protected $signature = 'demo:cleanup {--hours=24 : Delete demo users older than this many hours}';

    protected $description = 'Delete demo users and their associated data older than the specified threshold';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $demoUsers = User::where(function ($q) {
            $q->where('email', 'like', '%@demo.postvisit.ai')
                ->orWhere('email', 'like', 'demo-%@postvisit.ai');
        })
            ->where('email', '!=', 'doctor@demo.postvisit.ai')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($demoUsers->isEmpty()) {
            $this->info('No demo users older than '.$hours.' hours found.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($demoUsers as $user) {
            if ($user->patient_id) {
                $patient = Patient::find($user->patient_id);
                if ($patient) {
                    // Delete related data (visits cascade to visit_notes, transcripts, etc.)
                    $patient->visits()->each(function ($visit) {
                        $visit->visitNote?->delete();
                        $visit->transcript?->delete();
                        $visit->observations()->delete();
                        $visit->prescriptions()->delete();
                        $visit->chatSessions()->each(function ($session) {
                            $session->messages()->delete();
                            $session->delete();
                        });
                        $visit->conditions()->delete();
                        $visit->notifications()->delete();
                        $visit->delete();
                    });
                    $patient->forceDelete();
                }
            }

            $user->tokens()->delete();
            $user->delete();
            $count++;
        }

        $this->info("Cleaned up {$count} demo user(s) and their associated data.");

        return self::SUCCESS;
    }
}
