#!/bin/bash

# The default flags are picked up from country-flag-icons by Wil Linssen:
# https://github.com/linssen/country-flag-icons
path_to_svg=../country-flag-icons/images/svg

svgs=""
for alpha2 in sa by bg cz dk de gr gb es ee fi fr ie in hr hu id is it \
               il jp kr lt lv mk my mt nl no pl pt ro ru sk si al rs se \
               th tr ua vn cn ; do
    # Convert an alpha-2 code to an alpha-3 code according to ISO 3166-1
    alpha3=$(grep "^$alpha2" map|cut -f2)
    svgs="$svgs $path_to_svg/$alpha3.svg"
done

# United nation flag is not supported by the country-flag-icons project,
# so download a copy directly from wikipedia
wget -qOun.svg https://upload.wikimedia.org/wikipedia/commons/2/2f/Flag_of_the_United_Nations.svg
svgs="$svgs un.svg"

montage $svgs -tile 1x -resize 14x11\! -geometry '14x11>+0+0' -gravity NorthWest small.png
montage $svgs -tile 1x -resize 22x16\! -geometry '22x16>+0+0' -gravity NorthWest medium.png
montage $svgs -tile 1x -resize 30x22\! -geometry '30x22>+0+0' -gravity NorthWest large.png

montage  small.png medium.png large.png -mode Concatenate -background transparent -tile 1x4 sprite.png
pngcrush sprite.png languages.png

rm un.svg small.png medium.png large.png sprite.png
