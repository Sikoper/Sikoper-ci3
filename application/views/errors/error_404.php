<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Sikoper</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/css/bootstrap.css">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/css/app.css">
    <link rel="stylesheet" href="<?= base_url('assets') ?>/css/pages/error.css">
    <link rel="icon" href="<?= base_url('assets') ?>/images/logo/sikoper.png">
</head>

<body>
    <div id="error">
        <div class="error-page container">
            <div class="col-md-8 col-12 offset-md-2 text-center">
                <img class="img-error img-fluid" style="max-width: 80%;" src="<?= base_url('assets') ?>/images/samples/error-404.png" alt="Not Found">
                <div>
                    <h1 class="error-title">NOT FOUND</h1>
                    <p class='fs-5 text-gray-600'>The page you are looking for was not found.</p>
                    <a href="<?= base_url('/') ?>" class="btn btn-lg btn-outline-primary mt-3">Go Home</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>