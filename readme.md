# Open Graph Image Overlay

Generate Open Graph images automatically with an image overlay of your choice. Showing off your branding just got a little easier 👌

This plugin currently works alongside with two popular SEO plugins.

1. Yoast SEO
1. Rank Math

[Learn more about this plugin here](https://itsmereal.com/plugins/open-graph-image-overlay)

## Important Notes

1. As mentioned above, this plugin will only work automatically if you are using **"Yoast SEO"** or **"Rank Math"** as your SEO plugin. If you use something else, you will need to filter the open graph image meta generated by your SEO plugin or manually use the image URL.
1. This plugin will not work properly if you are using/uploading different featured image sizes. Your uploaded featured images **MUST BE THE SAME SIZE**. While the recommended size is **1200 x 630** pixels, you may choose to use different size. However, all the featured images need to be the same dimention.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/og-image-overlay` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Go to Settings > OG Image Overlay to access plugin settings.

## Usage

- Install and activate the plugin. Got to Settings > OG Image Overlay and update the plugin settings.
- Select a fallback image
- Select the overlay image
- Set X and Y value to position the overlay image
- If you are using Yoast or Rank Math for SEO, choose the correct one in the settings otherwise it will not work.

## Video Walkthrough

[![Watch the video](https://itsmereal.com/wp-content/uploads/2020/07/ogio-video.png)](https://vimeo.com/437133732)

[ভিডিওটি বাংলায় দেখতে চাইলে এখানে ক্লিক করুন](https://www.youtube.com/watch?v=AmYM-_w-K7I)

## FAQs

- **I am not using Yoast or Rank Math, what can I do?**\
  If you are not using one of them but still want to use overlay image on top of your open graph images, you can manually set the link. You can get the generated URL to use from the plugin settings page (look at the right side preview with instructions).
- **Do I need to upload certain sized image?**\
  It is best to use 1200px by 630px images as the source. For the overlay it can be any size that fits the source image size.
- **Can I use different image source for the Open Graph Image?**\
  Since version 1.5, you will be able to use image added to the Social tab on your Yoast SEO settings under each post.
- **How do I know what to use for X and Y in the plugin settings?**\
  X value is for the X axis and Y value is for the Y axis of the final image. You have to carefully measure the width and height and set the correct values. The preview shown on the plugin settings page can be wrong if not used properly (the recommended 1200px by 630px). So, always test your output image first. You can easily check for the generated image url from page source. Here is an example output.\
  ![Sample Output](https://itsmereal.com/wp-content/uploads/2022/10/ogio-link-preview.png)

- **I already have a different sized featured image dimention for my featured size, how do I change it?**\
  You will need to replace your uploaded images if you want to use the recommended 1200 x 630 pixels dimention or a new dimention that you want to use for all your uploads in future. You can replace your current media files with a handy plugin called [Enable Media Replace](https://wordpress.org/plugins/enable-media-replace/).

## License

GNU General Public License v2.0
