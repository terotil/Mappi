#!/bin/sh
# -------------------------------------
# Mapin entrypointin teko
#  1) Linkitet‰‰n skriptit ja hakemistot
#  2) Kopioidaan malliconffi
#  2) Kopioidaan malli j‰rjestelm‰kohtaisesta p‰‰sivusta
# -------------------------------------
# $Id: luo_entrypoint.sh,v 1.10 2006/10/05 14:07:00 mediaseitti Exp $

# Tarkista linkitysparametri
if [[ $2 == '-s' ]] ; then
    LNOPTS=''
else
    LNOPTS='-s'
fi

# Tarkista, ett‰ annettiin tarvittava hakemistoparametri
if [[ $1 == '' ]] ; then

    # Ei parametreja, tulosta k‰yttˆohje
    echo "K‰yttˆ:"
    echo "    luo_entrypoint.sh /mapin/hakemisto [-s]"
    echo ""
    echo "Mapin entrypointin luonti.  T‰m‰ ohjelma linkitt‰‰ Mapin toiminnan"
    echo "kannalta tarpeelliset tiedostot nykyiseen hakemistoon, sek‰ kopioi"
    echo "esimerkit j‰rjestelm‰kohtaisesti muokattavista tiedostoista."
    echo ""
    echo "Optiot:"
    echo "    -s   Kovat linkit ja oikeudet suexec:i‰ varten."

else

    # Tarkistetaan, ett‰ parametrina annettu hakemisto todella on
    # olemassa
    if [[ -d $1 ]]; then

	#  1) 
	# Linkitet‰‰n skriptit
	if  [[ -f $1/skriptit/linkfiles.txt ]] ; then
	    for file in `cat $1/skriptit/linkfiles.txt`; do 
		# Jos linkki jo on olemassa, poistetaan ja luodaan uudestaan
		if [[ -e $file ]] ; then rm $file; fi
		ln $LNOPTS $1/$file
		if [[ $2 == '-s' ]] ; then
		    chmod 700 ./$file
		else
		    chmod u=rw,g=rw,o=r ./$file
		fi
	    done
	else
	    echo "Tarvittava tiedosto $1/skriptit/linkfiles.txt puuttuu!"
	    exit 1
	fi

	# Linkitet‰‰n k‰yttˆliittym‰n m‰‰rittely
	# T‰m‰ tehd‰‰n aina symbolisin linkein, koska n‰it‰ ei ajeta.
	for file in .css _headers.php _html-body-begin.php _html-body-end.php _html-head.php ; do
	    if  [[ -e ./mappi$file ]] ; then rm ./mappi$file ; fi
	    ln -s $1/mappi$file
	done

	# Linkitet‰‰n hakemistot.  T‰‰ll‰ k‰ytet‰‰n aina symbolisia
	# linkkej‰.
	for file in help MAPPI ; do
	    if  [[ -e ./$file ]] ; then rm ./$file ; fi
	    ln -s $1/$file
	done

	# Linkitet‰‰n muutamia yleisk‰yttˆisi‰ raportteja
	for file in input vajaa ; do
	    if [[ -e raportti_$file.php ]] ; then rm raportti_$file.php; fi
	    ln $LNOPTS $1/raportteja/raportti_$file.php
	    if [[ $2 == '-s' ]] ; then
		chmod 700 ./raportti_$file.php
	    fi
	done

	#  2)
	# Kopioidaan malliconffi (ei edellisen p‰‰lle)
	if [[ ! -f ./mappi_rc.php ]] ; then
	    cp $1/mappi_rc.php .
	    # Varmistetaan oikeudet, suexecin kanssa tiukemmat
	    if [[ $2 == '-s' ]] ; then
		chmod 600 ./mappi_rc.php
	    else
		chmod u=rw,g=rw,o=r ./mappi_rc.php
	    fi
	else
	    echo "Vanhat asetukset (./mappi_rc.php) j‰tetty ennalleen."
	fi

	#  3) 
	# Kopioidaan malli j‰rjestelm‰kohtaisesta p‰‰sivusta
	if [[ ! -f ./welcome_local.php ]] ; then
	    cp $1/welcome_local.php .
	    # Varmistetaan oikeudet, suexecin kanssa tiukemmat
	    if [[ $3 == '-s' ]] ; then
		chmod 600 ./welcome_local.php
	    else
		chmod u=rw,g=rw,o=r ./welcome_local.php
	    fi
        else
            echo "Vanhat p‰‰sivu (./welcome_local.php) j‰tetty ennalleen."
      	fi

	# Varmistetaan, ett‰ hakemiston oikeudet t‰sm‰‰v‰t
	# suexec-k‰yttˆˆn jos kyseinen optio oli valittuna
	if [[ $2 == '-s' ]] ; then
	    chmod 711 .
	fi

    else

	echo "Hakemistoa $1 ei ole olemassa."

    fi

fi
