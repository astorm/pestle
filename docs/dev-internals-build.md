#TODO: finish

## Building Pestle

Alright!  You've made your changes and you're ready to do some end-to-end testing.  In other words, you want to build pestle's `.phar` file and see if your stuff actually works.

Pestle uses the [venerable phing build tool](https://www.phing.info/) to create its `phar` file.  Unfortunately, when PHP started shipping with the ini setting `phar.readonly` set to `1`, they semi-broke our build.  In order to build a `.phar`, you'll need to invoke `phing` with the following.

```
$ php -d phar.readonly=0 vendor/bin/phing package_phar
```

Once this command finishes running, you should have a brand new `pestle.phar` in your root project directory.

```
$ ls pestle.phar
pestle.phar
```

The eagle eyes among you may have noticed a `build.sh` script.  This script is part of our semi-automated process of releasing a new version of pestle.  Pay it no mind, as we're [working towards 100% automation](https://github.com/astorm/pestle/issues/472) for the build-and-release process.

