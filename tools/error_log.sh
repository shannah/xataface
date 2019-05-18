#!/bin/sh

awk "/${1}>/{flag=1}/<${1}/{flag=0}flag" $2