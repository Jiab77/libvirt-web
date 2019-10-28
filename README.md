# libVirt Web
A simple web interface based on libVirt.

> This project is still a **Work In Progress**, it might not work correctly on your side.
>
> Please, create an issue in this case so I can track and fix it.
>
> Thank you.

# Preview
![image](https://user-images.githubusercontent.com/9881407/66279294-ed9eb480-e8b0-11e9-8382-c6fa65313ee0.png)
![image](https://user-images.githubusercontent.com/9881407/66279362-4b330100-e8b1-11e9-9b65-b78164269978.png)

# Installation
The installation process is pretty simple and will require only few dependencies.

The web interface should be able to run on desktops and servers.

## Dependencies
There is only few dependencies required, you will need at least five packages:

 1. `libvirt-bin` (The `virsh` command should be provided by [libVirt](https://libvirt.org/))
 2. `virt-viewer`
 3. `php-cli`
 4. `php-xml`
 5. `php-json`

> I have dropped the ImageMagick `convert` command from dependencies.

## Plaforms
The project has been tested on [Pop_OS!](https://system76.com/pop), a Linux distribution based on [Ubuntu 18.04 LTS](https://wiki.ubuntu.com/BionicBeaver/ReleaseNotes).

It is also tested on FreeBSD by my friend [@Sevendogs5](https://twitter.com/Sevendogs5).

### Ubuntu and derivated distribs
You should only need to install these packages:

```bash
sudo apt install libvirt-bin virt-viewer php-cli php-xml php-json
```

> I still need to validate the packages list so this might change later.

### FreeBSD
Instruction will be provided soon.

## Run the web interface
You can run the web interface by using the embedded web server from `PHP` or using `apache` or `nginx`.

### PHP Embedded Web Server
You can start the server that way:

```bash
cd libvirt-web
php -S localhost:8000 libvirt-web.php
```

> `sudo` is not required to run the server. It is required only if you want to run the server on a port below **1024**.
>
> You can also choose any other ports than **8000**.

Then navigate to http://localhost:8000 with your internet browser.

> I'm using Chromium but it should work on any other modern browser.

### Apache / nginx
This setup is not tested yet and will be documented later.

# Breaking Changes
I've changed completely the project structure and now the code is splitted into several files instead of keeping everything into a single file.

The more I was adding features and cleaner logic, the more it became difficult to maintain and keep it readable and understandable.

So the best solution that came to me was split the single file into several ones, now it's much more easier to maintain the project.

A single file version still exist if you look at the file `libvirtweb.aio.php` but it will not be supported anymore.

# Missing / Not Working
Here will be listed missing features / those not working correctly.

 * Remote connection on VM's using `virt-viewer`.
   * Works on local only...
 * Connection to remote hypervisor.
   * Not implemented yet / not correctly...
 * ISO image upload.
   * The upload is working but the uploaded file can't be moved to `/var/lib/libvirt/images`...
   * This is due to access restricted to `sudoers` with filesystem permissions.

# Thanks
Thanks to the respective developers for their amazing work.

Huge thanks to [Ingmar Decker](http://www.webdecker.de) for the `PPM` Image Reader `PHP` class.

Also thanks to my friend [@Sevendogs5](https://twitter.com/Sevendogs5) for supporting the FreeBSD platform.

# Contributions
Feel free to contribute by creating pull requests or new issues.

# Contact
You can reach me on Twitter by using [@jiab77](https://twitter.com/jiab77).