<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Testimonial')</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 2rem; }
        .public-container { max-width: 640px; margin: 0 auto; }
        .public-container h1 { font-size: 1.75rem; margin-bottom: 0.25rem; }
        .public-container h2 { font-size: 1.125rem; font-weight: 400; color: #555; margin-top: 0; }
        .public-container label { display: block; margin-top: 1rem; font-weight: 600; }
        .public-container input[type=text],
        .public-container input[type=email],
        .public-container input[type=url],
        .public-container textarea { width: 100%; padding: 0.5rem; font-size: 1rem; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .public-container textarea { min-height: 120px; }
        .public-container .required { color: #c00; }
        .public-container .consent { margin-top: 1.5rem; padding: 1rem; background: #f5f5f5; border-radius: 4px; }
        .public-container .submit { margin-top: 1.5rem; }
        .public-container button { padding: 0.75rem 1.5rem; font-size: 1rem; cursor: pointer; }
        .public-container .errors { background: #fee; border: 1px solid #fcc; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .public-container .errors ul { margin: 0; padding-left: 1.25rem; }
        .public-container .confirmation { text-align: center; padding: 3rem 1rem; }
        .public-container .confirmation h1 { font-size: 2rem; }
    </style>
</head>
<body>
    <div class="public-container">
        @yield('content')
    </div>
</body>
</html>
