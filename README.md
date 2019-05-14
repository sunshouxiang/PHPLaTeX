# PHPLaTeX - embed and run PHP snippets in LaTeX file

Embed PHP snippets in LaTeX files to bring convenience of programming.

## Setup

### Install texlive packages

    sudo apt-get install texlive
    sudo apt-get install texlive-xetex
    sudo apt-get install texlive-lang-cjk

### Install fonts

Collect and install following fonts.

    /usr/local/share/fonts/opentype/adobe:
        AdobeFangsongStd-Regular.otf
        AdobeHeitiStd-Regular.otf
        AdobeKaitiStd-Regular.otf
        AdobeMingStd-Light.otf
        AdobeMyungjoStd-Medium.otf
        AdobeSongStd-Light.otf
        HYQiHei-45S.otf
        KozGoPr6N-Medium.otf
        KozGoPro-Medium.otf
        KozGoProVI-Medium.otf
        KozGoStd-Bold.otf
        KozGoStd-ExtraLight.otf
        KozGoStd-Heavy.otf
        KozGoStd-Light.otf
        KozGoStd-Medium.otf
        KozGoStd-Regular.otf
        KozMinPr6N-Regular.otf
        KozMinProVI-Regular.otf
        KozMinStd-Bold.otf
        KozMinStd-ExtraLight.otf
        KozMinStd-Heavy.otf
        KozMinStd-Light.otf
        KozMinStd-Medium.otf
        KozMinStd-Regular.otf
        NeutraText-BookAlt.otf
        NeutraText-DemiAlt.otf
        NeutraText-LightAlt.otf

    /usr/local/share/fonts/truetype/microsoft:
        century.ttf
        cour.ttf
        courbd.ttf
        courbi.ttf
        couri.ttf
        mriam.ttf
        msgothic.ttc
        msmincho.ttc
        msyh.ttf
        msyhbd.ttf
        segoeui.ttf
        simfang.ttf
        simhei.ttf
        simkai.ttf
        simpo.ttf
        simsun.ttc
        times.ttf
        timesbd.ttf
        timesbi.ttf
        timesi.ttf

### Create local TeX ls-R database

    ./setup.sh

### Register path of executables

In case of bash

    TD=`pwd`
    export PATH="${TD}/bin:${PATH}"

In case of tcsh

    TD=`pwd`
    setenv PATH "${TD}/bin:${PATH}"

## Usage

    phplatex [options] texfile ...
    Options
        -d              print generated TeX content but do not compile
        -k              do not delete temporary working directory
        -m <alt_magic>  specify magic string, the default is "%"
        -p              print PHP program but do not run
        -r <rounds>     rounds to run LaTeX command.
        -s              compile eah TeX file individually.

## Processes

1. Convert TeX files with PHP snippets to pure PHP files.

2. Run these PHP files to generate pure TeX files.

3. Compile generated TeX files to PDF files.

## Embed PHP snippets

Each snippet should be started with a magic string. ByAll magic strings start with '%%' by default. You may replace the second '%' with '-m' option.

### LaTeX command

At the beginning of line.

    %%# xelatex %TEXFILE%

If there is no %%# lines, the default command

    xelatex -shell-escape %TEXFILE%

is used.

Variable substitution:

    %TEXFILE%   the basename with extension of TeX file
    %CWD%       the startup directory

Multiple LaTeX commands can be specified, in which case, they are run in the order of appearance.

### Pure PHP line

Magic position: beginning of line.

    %%! echo "Hello, world";

### Pure TeX line

Magic position: beginning of line.

    %%= {\color{red}Hello, world!}\newline

May be used in HereDoc blocks.

### Pure TeX line ignored by PHPLaTeX

Magic position: beginning of line.

    {\color{red}Hello, world!}\newline %%=

This line will be ignored by PHPLaTeX and availabe when you compile the TeX file with LaTeX command directory.

### PHP code block

Magic position: beginning of line.

    %%{
        echo "Hello, world!";
    %%}

or

    \iffalse%%{
        echo "Hello, world!";
    \fi%%}

### Inline PHP variable

Magic position: inline.

    {\color{red}{<? $hello ?>}}

will be converted to

    echo "{\\color{red}";echo "{$hello}";echo "}";

### Inline PHP statement

Magic position: inline.

    {\color{red}{<?php echo "$hello"; ?>}}

will be converted to

    echo "{\\color{red}";echo "$hello";echo "}";

### Single HereDoc block

Magic position: beginning of line.

    %%<<<name echo $name;
    Hello, world!
    %%>>>

will be converted to

    $name = <<<name_EOT
    Hello, world!
    name_EOT;
    echo $name;

### Multiple HereDoc blocks

Magic position: beginning of line.

    %%<<<*names
    %%! foreach ($names as $name) {
    %%!     echo $name;
    %%! }
    Hello, world!

    Hello, universe!
    %%>>>

will be converted to

    $names_0 = <<<names_0_EOT
    Hello, world!
    names_0_EOT;
    $names_1 = <<<names_1_EOT
    Hello, universe!
    names_1_EOT;
    $names = [
    $name_0,
    $name_1
    ];
    foreach ($names as $name) {
        echo $name;
    }

## Library

The generated PHP files require library file phplatex_lib.php, which further more requires pytext-table.php.

pytext-table.php defines a Pinyin table of Chinese characters.

### 1. zy

#### Function

    Generate phonetic notation with Pinyin table for specified Chinese characters

#### Prototype

    zy($text, $grid = false, $note_spec)

#### Example

    zy("门修斯@{王北}与常凯申@{王清}", false, "footnote")

will return

    \zy{\pymen2}{门}\zy{\pyxiu1}{修}\zy{\pysi1}{斯}{\zynotemark\zynotetext{{}王北}}\zy{\pyyu3}{与}\zy{\pychang2}{常}\zy{\pykai3}{凯}\zy{\pyshen1}{申}{\zynotemark\zynotetext{{}王清}}

### 2. print_zy

#### Function

    Print text generated by zy.

#### Prototype

    print_zy($text, $grid = false, $note_spec)

### 3. cphrases

#### Function

    Typeset Chinese phrases with grid and phonetic notation.

#### Prototype

    function cphrases($p, $line_chars, $space_chars)

#### Example

    cphrases("一穷二白 三朋四友 五颜六色 七上八下 九儒十丐", 16, 0.25)

will return

    \gzyni\cz{%
    \makebox[\textwidth][s]{\gzy{\pyyi1}{一}\gzy{\pyqiong2}{穷}\gzy{\pyer4}{二}\gzy{\pybai2}{白} \gzy{\pysan1}{三}\gzy{\pypeng2}{朋}\gzy{\pysi4}{四}\gzy{\pyyou3}{友} \gzy{\pywu3}{五}\gzy{\pyyan2}{颜}\gzy{\pyliu4}{六}\gzy{\pyse4}{色}}\vspace{1ex}\gzynl
    \gzy{\pyqi1}{七}\gzy{\pyshang4}{上}\gzy{\pyba1}{八}\gzy{\pyxia4}{下} \gzy{\pyjiu3}{九}\gzy{\pyru2}{儒}\gzy{\pyshi2}{十}\gzy{\pygai4}{丐}\vspace{1ex}\gzynl
    }

### 4. print_cphrases

#### Function

    Print text generated by cphrases.

#### Prototype

    function cphrases($p, $line_chars, $space_chars)

## Fonts

Font file can be downloaded from Adobe or Microsoft sites.

### Adobe

    AdobeSongStd-Light.otf
    AdobeFangsongStd-Regular.otf
    AdobeHeitiStd-Regular.otf
    AdobeKaitiStd-Regular.otf

### Microsoft

    simsun.ttc
    simfang.ttf
    simhei.ttf
    simkai.ttf

## Gezhu (割注)

The LaTeX package gezhu created by Yin Dian is used.
