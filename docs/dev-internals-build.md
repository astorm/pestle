## Building Pestle

Alright!  You've made your changes and you're ready to do some end-to-end testing.  In other words, you want to build pestle's `.phar` file and see if your stuff actually works.

Pestle uses the [venerable phing build tool](https://www.phing.info/) to create its `phar` file.  Unfortunately, when PHP started shipping with the ini setting `phar.readonly` set to `1`, they semi-broke our build system.  In order to build a `.phar`, you'll need to invoke `phing` with the following.

```
$ php -d phar.readonly=0 vendor/bin/phing package_phar
```

This uses PHP `-d` command line option to set the `phar.readonly=0` value to zero, and then invokes the `package_phar` build step.

Once this command finishes running, you should have a brand new `pestle.phar` in your root project directory.

```
$ ls pestle.phar
pestle.phar
```

## Build a Release

Right now the release process is only semi-automated. We're [working towards 100% automation](https://github.com/astorm/pestle/issues/472), but something something cobbler's children.

To build a release, we

1. Create a fresh clone of the repo
2. Run `composer install`
3. Update the version string in `modules/pulsestorm/pestle/version/module.php`
4. Build the library by running `./build-library.bash`
5. Commit your changes.
6. Tag the current commit with the version from #3
7. Push the tags (`git push --tags`)
8. Check Travis
9. Once travis is passing, run `build.sh` to build the latest `.phar`
10. Upload the `phar` to `http://pestle.pulsestorm.net/pestle.phar`

Right now, every step here can be completed by anyone _except_ #10, which requires access credentials to pestle.pulsestorm.net.

If you love build systems and have opinions on way to automate this, [your feedback and participation is welcome](https://github.com/astorm/pestle/issues/472).
