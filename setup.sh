#!/bin/sh

TD=`pwd`
rm -rf ~/texmf/tex/latex
mkdir -p ~/texmf/tex/latex
ln -s $TD/packages ~/texmf/tex/latex/xelatex
cd ~/texmf
mktexlsr .
cd $TD
