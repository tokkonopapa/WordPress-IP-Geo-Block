<?php
/**
 * Alternative library for BCMath extension
 *
 */
if ( extension_loaded('gmp') ):

if ( ! function_exists('bcadd') ):
function bcadd( $Num1 = '0', $Num2 = '0', $Scale = null ) {
	return gmp_strval( gmp_add( $Num1, $Num2 ), 10 );
}
endif;

if ( ! function_exists('bcsub') ):
function bcsub( $Num1 = '0', $Num2 = '0', $Scale = null ) {
	return gmp_strval( gmp_sub( $Num1, $Num2 ), 10 );
}
endif;

if ( ! function_exists('bcmul') ):
function bcmul( $Num1 = '0', $Num2 = '0' ) {
	return gmp_strval( gmp_mul( $Num1, $Num2 ), 10 );
}
endif;

if ( ! function_exists('bcpow') ):
function bcpow( $num, $power ) {
	return gmp_strval( gmp_pow( $num, $power ), 10 );
}
endif;

else: // extension_loaded('gmp')

if ( ! function_exists('bcadd') ):
/**
 * bcadd — Add two arbitrary precision numbers.
 * @link http://php.net/manual/en/function.bcadd.php
 */
function bcadd($Num1='0',$Num2='0',$Scale=null) {
  // check if they're valid positive numbers, extract the whole numbers and decimals
  if(!preg_match("/^\+?(\d+)(\.\d+)?$/",$Num1,$Tmp1)||
     !preg_match("/^\+?(\d+)(\.\d+)?$/",$Num2,$Tmp2)) return('0');

  // this is where the result is stored
  $Output=array();

  // remove ending zeroes from decimals and remove point
  $Dec1=isset($Tmp1[2])?rtrim(substr($Tmp1[2],1),'0'):'';
  $Dec2=isset($Tmp2[2])?rtrim(substr($Tmp2[2],1),'0'):'';

  // calculate the longest length of decimals
  $DLen=max(strlen($Dec1),strlen($Dec2));

  // if $Scale is null, automatically set it to the amount of decimal places for accuracy
  if($Scale==null) $Scale=$DLen;

  // remove leading zeroes and reverse the whole numbers, then append padded decimals on the end
  $Num1=strrev(ltrim($Tmp1[1],'0').str_pad($Dec1,$DLen,'0'));
  $Num2=strrev(ltrim($Tmp2[1],'0').str_pad($Dec2,$DLen,'0'));

  // calculate the longest length we need to process
  $MLen=max(strlen($Num1),strlen($Num2));

  // pad the two numbers so they are of equal length (both equal to $MLen)
  $Num1=str_pad($Num1,$MLen,'0');
  $Num2=str_pad($Num2,$MLen,'0');

  // process each digit, keep the ones, carry the tens (remainders)
  for($i=0;$i<$MLen;$i++) {
    $Sum=((int)$Num1{$i}+(int)$Num2{$i});
    if(isset($Output[$i])) $Sum+=$Output[$i];
    $Output[$i]=$Sum%10;
    if($Sum>9) $Output[$i+1]=1;
  }

  // convert the array to string and reverse it
  $Output=strrev(implode($Output));

  // substring the decimal digits from the result, pad if necessary (if $Scale > amount of actual decimals)
  // next, since actual zero values can cause a problem with the substring values, if so, just simply give '0'
  // next, append the decimal value, if $Scale is defined, and return result
//  $Decimal=str_pad(substr($Output,-$DLen,$Scale),$Scale,'0');
//  $Output=(($MLen-$DLen<1)?'0':substr($Output,0,-$DLen));
//  $Output.=(($Scale>0)?".{$Decimal}":'');
//  return($Output);
  return( $Output ? $Output : '0' );
}
endif; // ! function_exists('bcadd')

if ( ! function_exists('bcsub') ):
/**
 * bcsub — Subtract one arbitrary precision number from another.
 * @link http://php.net/manual/en/function.bcsub.php
 */
function bcsub($Num1='0',$Num2='0',$Scale=null) {
  // check if they're valid positive numbers, extract the whole numbers and decimals
  if(!preg_match("/^\+?(\d+)(\.\d+)?$/",$Num1,$Tmp1)||
     !preg_match("/^\+?(\d+)(\.\d+)?$/",$Num2,$Tmp2)) return('0');

  // this is where the result is stored
  $Output=array();

  // remove ending zeroes from decimals and remove point
  $Dec1=isset($Tmp1[2])?rtrim(substr($Tmp1[2],1),'0'):'';
  $Dec2=isset($Tmp2[2])?rtrim(substr($Tmp2[2],1),'0'):'';

  // calculate the longest length of decimals
  $DLen=max(strlen($Dec1),strlen($Dec2));

  // if $Scale is null, automatically set it to the amount of decimal places for accuracy
  if($Scale==null) $Scale=$DLen;

  // remove leading zeroes and reverse the whole numbers, then append padded decimals on the end
  $Num1=strrev(ltrim($Tmp1[1],'0').str_pad($Dec1,$DLen,'0'));
  $Num2=strrev(ltrim($Tmp2[1],'0').str_pad($Dec2,$DLen,'0'));

  // calculate the longest length we need to process
  $MLen=max(strlen($Num1),strlen($Num2));

  // pad the two numbers so they are of equal length (both equal to $MLen)
  $Num1=str_pad($Num1,$MLen,'0');
  $Num2=str_pad($Num2,$MLen,'0');

  // process each digit, keep the ones, carry the tens (remainders)
  for($i=0;$i<$MLen;$i++) {
    $Sum=((int)$Num1{$i}-(int)$Num2{$i});
    if(isset($Output[$i])) $Sum+=$Output[$i];
    $Output[$i]=$Sum%10;
    if($Sum>9) $Output[$i+1]=1;
  }

  // convert the array to string and reverse it
  $Output=strrev(implode($Output));

  // substring the decimal digits from the result, pad if necessary (if $Scale > amount of actual decimals)
  // next, since actual zero values can cause a problem with the substring values, if so, just simply give '0'
  // next, append the decimal value, if $Scale is defined, and return result
//  $Decimal=str_pad(substr($Output,-$DLen,$Scale),$Scale,'0');
//  $Output=(($MLen-$DLen<1)?'0':substr($Output,0,-$DLen));
//  $Output.=(($Scale>0)?".{$Decimal}":'');
//  return($Output);
  return( $Output ? $Output : '0' );
}
endif;  // ! function_exists('bcsub')

if ( ! function_exists('bcmul') ):
/**
 * bcmul — Multiply two arbitrary precision numbers.
 * @link http://php.net/manual/en/function.bcmul.php
 */
function bcmul($Num1='0',$Num2='0') {
  // check if they're both plain numbers
  if(!preg_match("/^\d+$/",$Num1)||!preg_match("/^\d+$/",$Num2)) return(0);

  // remove zeroes from beginning of numbers
  for($i=0;$i<strlen($Num1);$i++) {if(@$Num1{$i}!='0') {$Num1=substr($Num1,$i);break;}}
  for($i=0;$i<strlen($Num2);$i++) {if(@$Num2{$i}!='0') {$Num2=substr($Num2,$i);break;}}

  // get both number lengths
  $Len1=strlen($Num1);
  $Len2=strlen($Num2);

  // $Rema is for storing the calculated numbers and $Rema2 is for carrying the remainders
  $Rema=$Rema2=array();

  // we start by making a $Len1 by $Len2 table (array)
  for($y=$i=0;$y<$Len1;$y++)
    for($x=0;$x<$Len2;$x++)
      // we use the classic lattice method for calculating the multiplication..
      // this will multiply each number in $Num1 with each number in $Num2 and store it accordingly
      @$Rema[$i++%$Len2].=sprintf('%02d',(int)$Num1{$y}*(int)$Num2{$x});

  // cycle through each stored number
  for($y=0;$y<$Len2;$y++)
    for($x=0;$x<$Len1*2;$x++)
      // add up the numbers in the diagonal fashion the lattice method uses
      @$Rema2[Floor(($x-1)/2)+1+$y]+=(int)$Rema[$y]{$x};

  // reverse the results around
  $Rema2=array_reverse($Rema2);

  // cycle through all the results again
  for($i=0;$i<count($Rema2);$i++) {
    // reverse this item, split, keep the first digit, spread the other digits down the array
    $Rema3=str_split(strrev($Rema2[$i]));
    for($o=0;$o<count($Rema3);$o++)
      if($o==0) @$Rema2[$i+$o]=$Rema3[$o];
      else @$Rema2[$i+$o]+=$Rema3[$o];
  }
  // implode $Rema2 so it's a string and reverse it, this is the result!
  $Rema2=strrev(implode($Rema2));

  // just to make sure, we delete the zeros from the beginning of the result and return
  while(strlen($Rema2)>1&&$Rema2{0}=='0') $Rema2=substr($Rema2,1);

  return($Rema2);
}
endif; // ! function_exists('bcmul')

if ( ! function_exists('bcpow') ):
/**
 * bcpow — Raise an arbitrary precision number to another.
 * @link http://php.net/manual/en/function.bcmul.php
 */
function bcpow($num, $power) {
    $awnser = "1";
    while ($power) {
        $awnser = bcmul($awnser, $num, 100);
        $power = bcsub($power, "1");
    }
    return rtrim($awnser, '0.');
}
endif; // ! function_exists('bcpow')

endif; // extension_loaded('gmp')
?>