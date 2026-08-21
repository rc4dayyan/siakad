<?php

namespace Database\Seeders;

use App\Models\Dosen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DosenSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Demo123!';

    /**
     * Data identitas berasal dari dosens.sql yang diberikan untuk data demo.
     * Hash kata sandi dan ID numerik dari dump sengaja tidak disalin.
     */
    private const COMPRESSED_SOURCE = 'dZZJc6s4FIX3/SvY9aY7JQRm2LUcsJENAjPYwdUbT8/2S+zYeZ6S0o/vyxSpHnQ2Oa5z9ElIVxdUjhGykGoaSOWMOkRhWTzKGHEd4vGvt/F2Zc80tMgvclDIf/aH0/vH5Wl/vC3e9mvJ+QNiKsKqamowok+cOJvPSUAY/zgY/n3jj09bJ5EzQrao38431TYQ5kHmkSAgjkJ9EMrYIz51FOL/PSAx6ed8ZURfi/CnPzHuPXmckN0zFU41k4Y0W0M97sLClSTzSSH4betsfy2ZEy/Rs5wSsoNbOxVXR6rdg5WQOZ0rAQEwIwpNxoQ5JOYT33zbjryredcnclrIDn7tlPziz8Swp7nrUFh3FFPiUK6ny83xYzpx4/eFnBKyzW2ciotVbJowgjCq5JlP4X9K+Lx/Nsk9jk6bz1hOCdnBrZ2KC5tjFifjMuopHonTgsyNqT3bsHO8Pv9y5ZSQHdzaabimhZDG58RlxFegYFLYCMY1c6UOt8F6uUVDOSdkJ7l0KjJstWXDMzrulCpDwkYkBnySeuGA8Mt297VS9+H68jOQw0J24GunxqvY6sGCKBvSVCFsOCQJiSm/j5zN6eYnfds9yTkhu8iVU5FNFdnFoaew18oki8M08xVS/IA7NIdbP/7xeejn6mGnni15gJAdU9RONQW0AFsvqo/ksHIFnoAokRsHJC0fQhlCg5nBPfLX+Li+DFdBli3kcUJ2zFQ75UwwI7LgwHkQek/KmEDlKED2s+KMl4Oc+Qd94RpeIGeFbNMbp6JrCNtaWe1pqAzcPtwiUPyh49UA3/H9dfwlx4TsANdOAzYsC5biEQb7Di3Xg9abw96PjNXb9j6YqdFuKweF7ESXToXWkWZYxZrDSInDiI7pd1/5SwmeIueJPvHjZvu8uSZUe+gPeZCQHdPUTjVND5m2CiN8Os+KTY9TF3YHCtWpN0nX8fn55/bD8DY7eYCQHVPUTjOFbZWFSuKc8Ct9jubJ9PAyDnPZFbKTVzqCV3SlPEugdyXZQJmXZZ9CE4abZTrosPM9jybWLzku5P9M0DQxbCC7fETHdVxPSXLy75/EIcX1GpOA+nC8QYSt0ftgvFl6SB4hZMcctVPPAVfZlF99czKmeflenYXkpuNwNyJfH3JUyC545VRwCwqpaCaE5Aq8QqlHoBm78CD8EUyzLdM+b8celpNCdrBr55ttI6RzRka0D/RsTvkiUGfPn5Of0+hxljNCdlMLp6TChYKKKTsNS8uLxIrqo3xAlv2rGX28+6OdnBOyTW4cIBfXSYNZVJsn8E0RwNE9rPfDDfeyITomsi/k70ThlES1GUGGUIAjOLKqTQWLjffixkvyY5bJMSHbYFUGwzeXAT9M2NokLpGX6f5xYt5nnq/3ckDINrJxKmSziD7woKmOQr5KeyeP3e7TYzSRE0J2MOVlas2I4s4l8Kak8Ia/JIPXUx5kQfiI5YyQbaomU/VmhOfGmeKFbrUDyefzMr+tr5PVNZBTQra5usztifN3o8z3Q6iAM1uHB29yyXtfckLINrMnMy2k98pzKCsgyVKaMMJ7E+1evGwpGmRySMg2tnEqLG4Kq5/BtyRL8izm5zQw2O3q3Wa+JWeE7KBiabE2lssVmB70Mm4Em91w4uLZPHqRQ0K2sbaEtTGGnokwfPkGlCkxdYrvALye6i/TWN+9jW05I2SL+u2U1B4uLi/WuZvRRHHgbpUNgVvG6fV1Ndjf0B7LMSHb4MYBsA3rti3L1EyeUOgv0AqKl7SuZWQZbHf97MWTM0L+ThVOubdGUzo5FCu0ApeVRfsaXq3TMbWjobWWU0K2N7dx/gM=';

    public function run(): void
    {
        foreach (self::rows() as $attributes) {
            $lecturer = Dosen::query()->firstOrNew(['dsn_nidn' => $attributes['dsn_nidn']]);
            $isNew = ! $lecturer->exists;

            $lecturer->fill($attributes);

            if ($isNew) {
                $lecturer->dsn_image = 'default/default-profile.jpg';
                $lecturer->password = Hash::make(self::DEFAULT_PASSWORD);
            }

            $lecturer->save();
        }
    }

    /** @return list<array{dsn_stat: int, dsn_nidn: string, dsn_name: string, dsn_code: string, dsn_user: string, dsn_mail: string, dsn_phone: string}> */
    public static function rows(): array
    {
        $compressed = base64_decode(self::COMPRESSED_SOURCE, true);
        $source = $compressed === false ? false : gzinflate($compressed);

        if ($source === false) {
            throw new RuntimeException('Data sumber dosen tidak dapat dibaca.');
        }

        return array_map(function (string $line): array {
            [$status, $nidn, $name, $code, $username, $email, $phone] = explode('|', $line);

            return [
                'dsn_stat' => (int) $status,
                'dsn_nidn' => $nidn,
                'dsn_name' => str_replace("\\'", "'", $name),
                'dsn_code' => $code,
                'dsn_user' => $username,
                'dsn_mail' => $email,
                'dsn_phone' => $phone,
            ];
        }, explode("\n", $source));
    }
}
