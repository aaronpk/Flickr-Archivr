# Flickr Archivr

This project downloads your entire Flickr archive and turns it into a static website. 

You can browse the website directly from your filesystem or you can upload the photos to a web server to make it public. The photos are stored on disk organized by date so you can even browse the files just by navigating the folders on your computer.


## Getting Started

Install PHP and the `curl` and `simplexml` extensions. [Install Composer](https://getcomposer.org/download/). Run `composer install`.

Copy `.env.example` to `.env`

Start by creating an app for the Flickr API.
https://www.flickr.com/services/apps/create/apply/

Copy the API Key and Secret to the `.env` file.

Log in to get your access token:

```bash
php scripts/login.php
```

After successfully authorizing the app, it will give you two lines to add to your `.env` file.

```bash
FLICKR_ACCESS_TOKEN=
FLICKR_ACCESS_TOKEN_SECRET=
```

## Downloading your Flickr Archive

Choose a folder to download everything into. Put the full path to the folder in the `.env` file, make sure to include a trailing slash.

Make sure you have enough disk space in the chosen location! For reference, Flickr says I have 120gb of photos, and once this script downloaded all the different resolutions, it took 255gb on disk.

```bash
STORAGE_PATH=/path/to/photos/
```

Then start the download:

```bash
php scripts/download.php
```

Since Flickr sometimes throws a 500 server error randomly, you can instead run the wrapper script which will retry when errors are encountered.

```bash
./scripts/download.sh
```

This will also download your album and people metadata as well.


### Folder Structure

Photos are downloaded to a folder structure like the below, based on the date the photo was taken (if available) otherwise the date the photo was uploaded.

```
2022/
    /08/
       /12/
          /XXXXXXXXXXXXXX/
                         /info/photo.json
                         /info/sizes.json
                         /info/exif.json
                         /info/comments.json
                         /sizes/XXXXXXXXXXXXXX_k.jpg
                         /sizes/XXXXXXXXXXXXXX_b.jpg
                         /sizes/....
                         XXXXXXXXXXXXXX.jpg
          /XXXXXXXXXXXXXX/
                         /info/photo.json
                         /info/sizes.json
                         /info/exif.json
                         /info/comments.json
                         /sizes/XXXXXXXXXXXXXX_k.jpg
                         /sizes/XXXXXXXXXXXXXX_b.jpg
                         /sizes/....
                         XXXXXXXXXXXXXX.jpg
       /13/
          /XXXXXXXXXXXXXX/
                         /info/photo.json
                         /info/sizes.json
                         /info/exif.json
                         /info/comments.json
                         /sizes/XXXXXXXXXXXXXX_k.jpg
                         /sizes/XXXXXXXXXXXXXX_b.jpg
                         /sizes/....
                         XXXXXXXXXXXXXX.jpg
```

Each photo gets its own folder at: `YEAR/MONTH/DAY/PHOTO_ID/`. Inside the folder are:

* The original photo stored as `PHOTO_ID.jpg`
* A folder with every other size that Flickr provides, as `sizes/PHOTOID_SIZE.jpg`
* A folder with JSON files containing
  * `photo.json` - The photo info including title, description, dates, tags, etc
  * `exif.json` - The complete exif data
  * `sizes.json` - Info about all the sizes of the photo available
  * `comments.json` - If present, all the comments on the photo


### Download Albums

Albums (formerly known as photosets), can be downloaded with the command below.

```bash
php scripts/photosets.php
```

This creates a new folder with a subfolder for each album:

```
albums/
      /XXXXXXXXX/album.json
                /photos.json
      /XXXXXXXXX/album.json
                /photos.json    
```

The file `album.json` has the album metadata such as name and modified date. The file `photos.json` contains a list of all the photos in the album.


### Download People

If you've tagged people in your photos, you can download metadata about them so their name and link appears in your archive.

```bash
php scripts/downloadpeople.php
```


## Build Indexes

The indexes are used for various purposes when building the web pages to browse the photos. 

Since photos are stored in a folder by date, this index helps other parts of the system find the photos on disk by just their photo ID. 

```bash
php scripts/indexphotos.php
```

To build an index of all the people and which photos they appear in, run the command below.

```bash
php scripts/indexpeople.php
```

Build an index of all tags in order to create tag pages

```bash
php scripts/indextags.php
```

Note: You can build all indexes with the bash script included:

```bash
./scripts/index.sh
```

This just runs the three php scripts sequentially.



## Build the Site

After everything is downloaded, build the static website:

```bash
./scripts/build.sh
```

Now you can browse your website by opening up the storage folder in a browser! If you have the folder locally on disk, just open the `index.html` file. If you've run this on a remote server, you can configure your web server to serve that folder, or run the built in PHP server:

```bash
php -S 0.0.0.0:8080 -t photos
```

Replace `photos` with the path you configured as the `STORAGE_PATH` where your photos have been downloaded.


