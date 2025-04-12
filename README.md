# 🔒 Encryptable

A Laravel trait for automatically encrypting and decrypting model attributes using MySQL AES encryption.

✨ **Features:**
- Models attributes that are marked as `encryptable` will automatically be AES encrypted upon persisting to the database.
- Attributes are automatically decrypted upon retrieval via Eloquent model attributes or when serializing to JSON/array.
- Provides query builder helper methods for searching on the fields using Eloquent, supports both full and partial searching.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require striderwhite/encryptable
```

---

## 📖 Usage

1. **Database Setup:** Ensure the database column you wish to encrypt is of `binary` datatype.
2. **Add the Trait:** Add the `Encryptable` trait to your Eloquent model.
3. **Define Encryptable Fields:** Define an `$encryptable` property in your model with the list of attributes to encrypt.

```php
use StriderWhite\Encryptable;

class YourModel extends Model
{
    use Encryptable;

    protected $encryptable = ['field1', 'field2'];
}
```

4. 💾 **Persisting and Retrieving Data**

Using the `Encryptable` trait, you can persist and retrieve data just like normal Eloquent attributes. The encryption and decryption process is handled automatically.

#### Example

```php
use App\Models\YourModel;

// Persisting data
$model = new YourModel();
$model->field1 = 'Sensitive Data';
$model->field2 = 'Another Secret';
$model->save();

// Retrieving data
$retrievedModel = YourModel::find($model->id);
echo $retrievedModel->field1; // Outputs: Sensitive Data
echo $retrievedModel->field2; // Outputs: Another Secret
```

---

### 🔎 Searching Encrypted Fields

The package provides two query scopes for searching encrypted fields:

#### `scopeWhereEncrypted`

Use this scope to search for an exact match on an encrypted field:

```php
YourModel::whereEncrypted('field1', 'value')->get();
```

#### `scopeWhereEncryptedLike`

Use this scope to perform a partial search on an encrypted field:

```php
YourModel::whereEncryptedLike('field1', 'value')->get();
```

---

## 🙏 Credits

Developed and maintained by **Strider White**. If you find this package helpful, feel free to ⭐ the repository or contribute!

---

## 📜 License

This package is open-sourced software licensed under the [MIT license](LICENSE).