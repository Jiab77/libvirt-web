# libVirt Web

A simple web interface based on [libVirt](https://libvirt.org/) and [PHP](https://www.php.net/).

> This project is still a **Work In Progress**; it may not work correctly on your host.
>
> Please, create an issue in this case so I can track and fix it.
>
> Thank you.

_If you were looking for a `nodejs` version: <https://github.com/Jiab77/libvirt-web-nodejs>._

## Preview

![image](https://user-images.githubusercontent.com/9881407/66279294-ed9eb480-e8b0-11e9-8382-c6fa65313ee0.png)
![image](https://user-images.githubusercontent.com/9881407/66279362-4b330100-e8b1-11e9-9b65-b78164269978.png)

## Installation

The installation process is pretty simple and will require only few dependencies.

The web interface should be able to run on almost any desktop or server.

## Compatibility

The code should be compatible with the latest PHP version.

> Feel free to create a new issue in case you find something that is not working anymore after having updated your PHP version.

## Dependencies

There is only a few dependencies required: __*__

1. `libvirt-bin` (The `virsh` command should be provided by [libVirt](https://libvirt.org/))
2. `virt-viewer`
3. `virt-install`
4. `libguestfs`
5. `php-cli`
6. `php-gd`
7. `php-xml`
8. `php-json`

> I have dropped the ImageMagick `convert` command from dependencies.

_**\*** The name of the required packages might be different between Ubuntu and Arch Linux based distribs._

## Plaforms

The project has been tested on:

* [Pop_OS!](https://system76.com/pop) (_Ubuntu based distrib_)
* [CachyOS](https://cachyos.org) (_Arch Linux based distrib_)

And should be compatible with any Ubuntu or Arch Linux based distributions.

> It has also been tested on FreeBSD by my friend [@Sevendogs5](https://twitter.com/Sevendogs5).

### Ubuntu and derivated distribs

You should only need to install these packages:

```bash
# For desktop
sudo apt install libvirt-bin virt-install virt-viewer virtinst libguestfs-tools php-cli php-gd php-xml php-json

# For server
sudo apt install libvirt-bin virt-install virtinst libguestfs-tools php-cli php-gd php-xml php-json

# Restart
sudo reboot

# Check services status
systemctl status libvirt-bin.service libvirt-guests.service libvirtd.service -l
```

> I still need to validate the packages list so this might change later.

### Arch Linux and derivated distribs

You should only need to install these packages:

```bash
# For desktop
paru -S libvirt virt-install virt-viewer guestfs-tools php php-gd

# For server
paru -S libvirt virt-install guestfs-tools php php-gd

# Enable 'php-gd' extension
sudo sed -e 's/;extension=gd/extension=gd/' -i /etc/php/php.ini

# Restart
sudo reboot

# Check services status
systemctl status libvirtd.service libvirt-guests.service -l
```

> You can replace `paru` by `sudo pacman` if you don't have `paru` installed.

### FreeBSD

_Instructions are still missing at the moment._

## Run the web interface

You can run the web interface by using the embedded web server from `PHP` or using `apache` or `nginx`.

### PHP Embedded Web Server

You can start the server that way:

```bash
cd libvirt-web
./start-web-server.sh
```

If you want to run the server on another interface / port, you can also do the following:

```bash
# Set another listen interface (it will catch the first IP address in this case)
LISTEN_INTERFACE=`hostname -I | awk '{ print $1 }'` ./start-web-server.sh

# Set another listen interface (it will catch the FQDN in this case)
LISTEN_INTERFACE=`hostname -f` ./start-web-server.sh

# Set another list port
LISTEN_PORT=8888 ./start-web-server.sh

# Set another listen interface and port
LISTEN_INTERFACE=`hostname -f` LISTEN_PORT=8888 ./start-web-server.sh
```

> `sudo` __might be required__ to run the server if you want to run the server on a port below `1024` __and/or__ to access VMs created under the `root` user.
>
> In this case, you might have to do the following:
>
> `cd libvirt-web ; sudo ./start-web-server.sh`
>
> Or:
>
> `cd libvirt-web ; sudo LISTEN_PORT=8888 ./start-web-server.sh`
>
> If you need to set a different listen port or interface.

Then navigate to [http://localhost:8000](http://localhost:8000) (_or any other address you would have defined_) with your internet browser.

### Apache / nginx

This setup is not tested yet and will be documented later.

> You may have to make a symlink named `index.php` that points to the `libvirtweb.php` file.

## Breaking Changes

I've changed completely the project structure and now the code is splitted into several files instead of keeping everything into a single file.

The more I was adding features and cleaner logic, the more it became difficult to maintain and keep it readable and understandable.

So the best solution that came to me was split the single file into several ones, now it's much more easier to maintain the project.

A single file version still exist if you look at the file `libvirtweb.aio.php` but it will not be supported anymore.

## Missing / Not Working

Here will be listed missing features / those not working correctly.

* Dark mode 😅
* Remote connection on VMs using `virt-viewer`
  * Works on local only...
* Connection to remote hypervisor
  * Not implemented yet / not correctly...
* ISO image upload
  * The upload is working but the uploaded file can't be moved to `/var/lib/libvirt/images`... (_missing super user privileges_)
  * This is due to access restricted to `sudoers` with filesystem permissions.
* Graphic charts are still missing

## Supported Browsers

I'm using Chromium but it should work on any other modern browser.

## Thanks

Thanks to the respective developers for their amazing work.

Huge thanks to [Ingmar Decker](http://www.webdecker.de) for the `PPM` Image Reader `PHP` class.

Also thanks to my friend [@Sevendogs5](https://twitter.com/Sevendogs5) for supporting the FreeBSD platform.

## Contributions

Feel free to contribute by creating pull requests or new issues.

## Author

* __Jiab77__
