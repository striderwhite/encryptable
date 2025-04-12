<?php

namespace StriderWhite;

use Illuminate\Support\Facades\DB;

trait Encryptable
{
    /**
     * Boot method for the Encryptable trait.
     *
     * This method is automatically called when the trait is used in a model.
     * It hooks into the "saving" event of the model to encrypt specified fields
     * before they are saved to the database.
     *
     * @return void
     *
     * @throws \Exception If encryption fails or the encryption key is not properly set.
     *
     * Usage:
     * - Ensure the model using this trait has a property $encryptable which is an array
     *   of field names that need to be encrypted.
     * - The encryption is performed using MySQL's AES_ENCRYPT function.
     *
     * Process:
     * - The method retrieves the encryption key using self::getMysqlKey().
     * - It iterates over the fields defined in $encryptable.
     * - If a field is dirty (modified) and has a value, it encrypts the value using
     *   MySQL's AES_ENCRYPT function and updates the model's attributes with the encrypted value.
     */
    public static function bootEncryptable()
    {
        static::saving(function ($model) {
            $key = addslashes(self::getMysqlKey());

            foreach ($model->encryptable as $field) {
                if ($model->isDirty($field) && isset($model->attributes[$field])) {
                    $value = addslashes($model->attributes[$field]);
                    $model->attributes[$field] = DB::raw("AES_ENCRYPT('$value', '$key')");
                }
            }
        });
    }

    /**
     * Set the value of a given attribute.
     *
     * If the attribute is listed in the $encryptable array and the value is not null,
     * it assigns the value directly to the $attributes array, allowing the boot method
     * to handle encryption on save. Otherwise, it delegates to the parent setAttribute method.
     *
     * @param string $key   The name of the attribute.
     * @param mixed  $value The value to set for the attribute.
     * @return $this|mixed Returns the current instance if the attribute is encryptable,
     *                     otherwise returns the result of the parent setAttribute method.
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable) && !is_null($value)) {
            // Let the boot method encrypt it on save
            $this->attributes[$key] = $value;
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Overrides the getAttribute method to handle decryption of specified attributes.
     *
     * This method checks if the requested attribute is in the $encryptable array
     * and if it exists in the $attributes array. If so, it attempts to decrypt
     * the attribute value using MySQL's AES_DECRYPT function with a predefined key.
     *
     * @param string $key The name of the attribute being accessed.
     *
     * @return mixed The decrypted value of the attribute if it is encryptable,
     *               the original attribute value if it is not encryptable,
     *               or null if decryption fails or the attribute does not exist.
     *
     * @throws \Exception Logs an error if decryption fails.
     */
    public function getAttribute($key)
    {
        if (in_array($key, $this->encryptable) && isset($this->attributes[$key])) {
            $result = DB::selectOne(
                "SELECT CAST(AES_DECRYPT(?, ?) AS CHAR) AS decrypted",
                [$this->attributes[$key], self::getMysqlKey()]
            );

            return $result->decrypted ?? null;
        }

        return parent::getAttribute($key);
    }

    /**
     * Convert the model's attributes to an array.
     *
     * This method overrides the parent attributesToArray method to ensure
     * that any attributes listed in the $encryptable property are decrypted
     * before being included in the resulting array.
     *
     * @return array The array representation of the model's attributes, with
     *               encrypted fields decrypted.
     */
    public function attributesToArray()
    {
        $array = parent::attributesToArray();

        foreach ($this->encryptable as $field) {
            $array[$field] = $this->getAttribute($field); // decrypted value
        }

        return $array;
    }

    /**
     * Scope a query to filter results based on an encrypted field.
     *
     * This method allows querying a database table where a specific field is
     * encrypted using AES encryption. It decrypts the field using the provided
     * MySQL key and compares it to the given value.
     *
     * @param \Illuminate\Database\Query\Builder $query The query builder instance.
     * @param string $field The name of the encrypted database column.
     * @param mixed $value The value to compare against the decrypted field.
     * @return \Illuminate\Database\Query\Builder The modified query builder instance.
     */
    public function scopeWhereEncrypted($query, $field, $value)
    {
        return $query->whereRaw("CAST(AES_DECRYPT($field, ?) AS CHAR) = ?", [self::getMysqlKey(), $value]);
    }

    /**
     * Scope a query to filter results where an encrypted field matches a given value using a LIKE comparison.
     *
     * This method decrypts the specified field using AES_DECRYPT with the application's MySQL key
     * and performs a LIKE comparison against the provided value.
     *
     * @param \Illuminate\Database\Query\Builder $query The query builder instance.
     * @param string $field The name of the encrypted database column to filter.
     * @param string $value The value to match against the decrypted field using a LIKE comparison.
     * @return \Illuminate\Database\Query\Builder The modified query builder instance.
     */
    public function scopeWhereEncryptedLike($query, $field, $value)
    {
        return $query->whereRaw("CAST(AES_DECRYPT($field, ?) AS CHAR) LIKE ?", [self::getMysqlKey(), "%$value%"]);
    }

    /**
     * Retrieve the MySQL encryption key for AES-256 encryption.
     *
     * This method fetches the application key from the configuration,
     * decodes it if it is base64-encoded, and ensures it is trimmed
     * to 32 characters to comply with the AES-256 encryption key length.
     *
     * @return string The 32-character encryption key for MySQL AES-256.
     */
    protected static function getMysqlKey(): string
    {
        $key = config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return substr($key, 0, 32); // MySQL AES-256
    }
}
