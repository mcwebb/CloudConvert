# CloudConvert OOPy PHP Wrapper

Work in progress, expect major architecture revisions.

## Example Use
```php
$user = new CloudConvert\User ('YOUR_API_KEY');
$process = new CloudConvert\Process ('png', 'pdf', $user);

$process->upload("input.png", "pdf" );

if ($process->waitForConversion()) {
   $process->download("output.pdf");
    echo "Conversion done :-)";
} else {
    echo "Something went wrong :-(";
}
```