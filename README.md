# Boot

Repository to have bootstrap stuff installed and ready to use in a symfony project,
together with a few website examples.
You can just clone this repo and have a skeleton with basic symfony stuff and
the bootstrap part installed to work "out-of-the-box". If you prefer to do all
this manually, just follow the steps below.


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
            <link rel="stylesheet" href="{{ asset('components/css/bootstrap.css') }}" />
    ...
    </head>
    <body>
    ...
      {% block javascripts %}
        <!-- Optional JavaScript -->
        <!-- jQuery first, then Popper.js, then Bootstrap JS -->
        <script src="{{ asset('components/jquery.slim.js') }}"></script>
        <!-- The bootstrap bundle includes the optional popper.js -->
        <script src="{{ asset('components/js/bootstrap.bundle.min.js') }}"></script>
      {% endblock %}
    </body>

Done!
