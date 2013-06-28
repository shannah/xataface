<?php
    // $Id: spl_examples.php,v 1.1 2006/03/03 19:49:16 sjhannah Exp $

    class IteratorImplementation implements Iterator {
        function current() { }
        function next() { }
        function key() { }
        function valid() { }
        function rewind() { }
    }

    class IteratorAggregateImplementation implements IteratorAggregate {
        function getIterator() { }
    }
?>
