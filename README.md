guests 
=======

Create a guest user by typing his name in to the sharing dialog. The guest
will receive an email invite with a link to create an account. He has only access
to files which are shared with him.


Furthermore, the administrator has to whitelist the applications that guests can use.
By default settings,avatar,files_trashbin,files_versions,files_sharing,files_texteditor,activity,firstrunwizard,gallery are allowed.


## How to set up your frontend development environment

The front end section is based on the [Vue.js 2.0](https://vuejs.org/) framework. The build process is based on Webpack. To get started, you need the latest version of [node.js and npm](https://nodejs.org) installed.

***

### Setup

1. Change to the `/app/guests` folder and install all dependencies and run:

`npm install`

2. If no errors occur, you can get webpack started (watcher included) by running:

`npm run dev`

***

### Building

To build the uglified / minified muted version run the following:

`npm run build`
