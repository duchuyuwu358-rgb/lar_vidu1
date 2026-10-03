<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'user:make-admin {email : Gmail của tài khoản đã đăng ký}';

    protected $description = 'Cấp quyền admin cho một tài khoản Gmail đã xác minh';

    public function handle(): int
    {
        $user = User::where('email', strtolower($this->argument('email')))->first();

        if (! $user) {
            $this->error('Không tìm thấy tài khoản Gmail này.');
            return self::FAILURE;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->error('Tài khoản phải xác minh Gmail trước khi cấp quyền admin.');
            return self::FAILURE;
        }

        $user->update(['role' => 'admin']);
        $this->info("Đã cấp quyền admin cho {$user->email}.");

        return self::SUCCESS;
    }
}