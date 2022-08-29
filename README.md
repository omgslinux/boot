# Boot

Repository to reproduce the basic bootstrap website examples and other bootstra stuff

## Steps for creating this symfony project for bootstrap 4

    $ symfony new boot --version=5.4 --webapp
    $ cd boot
    $ composer require oomphinc/composer-installers-extender

Edit extra section in composer.json:

    "extra": {
        "symfony": {
            "allow-contrib": false,
            "require": "5.4.*"
        },
        "installer-types": ["component"],
        "installer-paths": {
            "public/components/": ["type:component"]
        }

Install bootstrap 4

    $ composer require components/bootstrap

Edit the base template to use the basic components

    <head>
    ...
            <link rel="stylesheet" href="{{ asset('/components/css/bootstrap.css') }}" />
    ...
    </head>
    <body>
    ...
          {% block javascripts %}
      <!-- Optional JavaScript -->
      <!-- jQuery first, then Popper.js, then Bootstrap JS -->
      <script src="{{ asset('/components/jquery.slim.js') }}"></script>
      <!-- The bootstrap bundle includes the optional popper.js -->
      <script src="{{ asset('/components/js/bootstrap.bundle.min.js') }}"></script>
          {% endblock %}
    </body>

Done!
