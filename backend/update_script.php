<?php DB::table('system')->where('key', 'db_version')->update(['value' => '5.0']); echo 'Updated DB version to 5.0';
