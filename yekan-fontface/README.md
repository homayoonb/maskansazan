# typeface-yekan

The CSS and web font files to easily self-host “Yekan”.

## Install

`npm install --save yekan-fontface`

## Use

Typefaces assume you’re using webpack to process CSS and files. Each typeface
package includes all necessary font files (woff2, woff) and a CSS file with
font-face declarations pointing at these files.

You will need to have webpack setup to load css and font files. Many tools built
with Webpack will work out of the box with Typefaces such as [Gatsby](https://github.com/gatsbyjs/gatsby)
and [Create React App](https://github.com/facebookincubator/create-react-app).

To use, simply require the package in your project’s entry file e.g.

```javascript
// Load Yekan typeface
import "yekan-fontface";
```

## About the Typefaces project.

My goal is to add all open source, persian fonts to NPM to simplify using great fonts in
our web projects.

If your favorite typeface isn’t published yet, [let me know](https://gitlab.com/GoldenHat/farsi-typeface)
