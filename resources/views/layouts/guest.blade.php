<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Testimonial SaaS')</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f7; margin: 0; padding: 0; }
        .guest-wrap { max-width: 420px; margin: 4rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .guest-wrap h1 { font-size: 1.5rem; margin: 0 0 1.5rem; }
        .guest-wrap label { display: block; margin-top: 1rem; font-weight: 600; }
        .guest-wrap input { width: 100%; padding: 0.5rem; font-size: 1rem; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .guest-wrap button { margin-top: 1.5rem; padding: 0.75rem 1.5rem; font-size: 1rem; cursor: pointer; }
        .guest-wrap .alt { margin-top: 1rem; font-size: 0.875rem; }
        .guest-wrap .errors { background: #fee; border: 1px solid #fcc; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .guest-wrap .errors ul { margin: 0; padding-left: 1.25rem; }
        .guest-wrap .status { background: #efe; border: 1px solid #cec; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="guest-wrap">
        @yield('content')
    </div>
</body>
</html>
