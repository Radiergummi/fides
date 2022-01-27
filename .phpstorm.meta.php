<?php

namespace PHPSTORM_META
{
    // Ensures forms will be resolved to their type correctly, so getData() works
    override(\Symfony\Bundle\FrameworkBundle\Controller\AbstractController::createForm(), map([
        '' => '@',
    ]));
}
