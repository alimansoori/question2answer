﻿function openTab(c,d){var a,b;b=document.getElementsByClassName("tab-panel");for(a=0;a<b.length;a++)b[a].style.display="none";b=document.getElementsByClassName("tablinks");for(a=0;a<b.length;a++)b[a].className=b[a].className.replace(" active","");document.getElementById(d).style.display="block";c.currentTarget.className+=" active"};