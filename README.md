# pdf-generator-bundle

PDF Generator Bundle based on `wkhtmltopdf`

# Requirements

- `xvfb` to be installed on your server (`apt-get install xvfb` on debian/ubuntu)

By default this bundle uses the `h4cc/wkhtmltopdf-i386` package's binary. 
To use your system binary, add this to your parameters.yml.dist file and do a `composer install`

```yaml
    wucdbm_pdf_generator.binary: wkhtmltopdf
```

# Register the bundle in your AppKernel

```php
<?php
public function registerBundles() {
    $bundles = [
        new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
        // Add WucdbmPdfGeneratorBundle to your AppKernel
        new \Wucdbm\Bundle\PdfGeneratorBundle\WucdbmPdfGeneratorBundle(),
    ];
}

```

# Basic Usage

```php
<?php 
/** @var \Wucdbm\Bundle\PdfGeneratorBundle\Generator\PdfGenerator $generator */
$generator = $container->get('wucdbm_pdf_generator.generator');
$filename = 'someFile.pdf';
// Get a PdfResult. The wkPrint and bootstrap methods both return a PdfResult
/** @var \Wucdbm\Bundle\PdfGeneratorBundle\Generator\PdfResult $result */
$result = $generator->wkPrint($html);
// The PdfResult is the result of the PDF generation. It has access to the temporary PDF file
$tempPdfPath = $result->realPath();
// return a Symfony Response
return $generator->bootstrap($html)->response($filename);
// return a Symfony Response and copy the file some place else
// The Generator does NOT save the files; it will unlink them as soon as the request is finished
// The copy() method returns PrintResult
return $generator->bootstrap($html)->copy('/some/location/someFile.pdf')->response($filename);
// And last, you can also get the PDF file contents as string
$contents = $generator->bootstrap($html)->contents();
```

# TODO 

- https://www.npmjs.com/package/chrome-headless-render-pdf
- http://weasyprint.readthedocs.io/en/latest/install.html