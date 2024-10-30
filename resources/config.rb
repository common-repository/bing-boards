# Require any additional compass plugins here.

# Set this to the root of your project when deployed:
http_path = "/"
css_dir = ""
sass_dir = "dev"
images_dir = "images"
javascripts_dir = ""

environment = :production

require 'fileutils'

# Add .min. to the filename
on_stylesheet_saved do |file|
  if File.exists?(file)
    filename = File.basename(file, File.extname(file))
    File.rename(file, filename + ".min" + File.extname(file))
  end
end

output_style = :compressed # by Compass.app 