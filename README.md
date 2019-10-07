# libVirt Web
A simple web interface based on libVirt.

# Preview
![image](https://user-images.githubusercontent.com/9881407/66279294-ed9eb480-e8b0-11e9-8382-c6fa65313ee0.png)
![image](https://user-images.githubusercontent.com/9881407/66279362-4b330100-e8b1-11e9-9b65-b78164269978.png)

# Installation
The installation process is pretty simple and will require only few dependencies.

The web interface should be able to run on desktops and servers.

## Dependencies
There is only few dependencies required, you will need at least four packages:

 1. `libVirt` (`virsh` should be provided by [libVirt](https://libvirt.org/))
 2. `virt-viewer`
 3. `php-cli`
 4. `php-json`

## Plaforms
The project has been tested on [Pop_OS!](https://system76.com/pop), a Linux distribution based on [Ubuntu 18.04 LTS](https://wiki.ubuntu.com/BionicBeaver/ReleaseNotes).

It is also tested on FreeBSD by my friend [@Sevendogs5](https://twitter.com/Sevendogs5).

### Ubuntu and derivated distribs
You should only need to install these packages:

```bash
sudo apt install libvirt libvirt-bin php-cli php-json
```

> I need to validate the packages list so this might change later.

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

# Thanks
Thanks to the respective developers for their amazing work.

Also thanks to my friend [@Sevendogs5](https://twitter.com/Sevendogs5) for supporting the FreeBSD platform.

# Contributions
Feel free to contribute by creating pull requests or new issues.

# Contact
You can reach me on Twitter by using [@jiab77](https://twitter.com/jiab77).