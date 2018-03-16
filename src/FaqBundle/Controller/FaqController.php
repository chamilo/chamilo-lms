<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Controller;

use Chamilo\FaqBundle\Entity\Category;
use Chamilo\FaqBundle\Entity\CategoryRepository;
use Chamilo\FaqBundle\Entity\Question;
use Chamilo\FaqBundle\Entity\QuestionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class FaqController.
 *
 * @package Chamilo\FaqBundle\Controller
 */
class FaqController extends Controller
{
    /**
     * Default index. Shows one category/question at a time. If you want to just show everything at once, use the
     * indexWithoutCollapse action instead.
     *
     * @param string $categorySlug
     * @param string $questionSlug
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($categorySlug, $questionSlug)
    {
        if (!$categorySlug || !$questionSlug) {
            $redirect = $this->generateRedirectToDefaultSelection($categorySlug, $questionSlug);
            if ($redirect) {
                return $redirect;
            }
        }

        // Otherwise get the selected category and/or question as usual
        $questions = [];
        $categories = $this->getCategoryRepository()->retrieveActive();
        $selectedCategory = $this->getSelectedCategory($categorySlug);
        $selectedQuestion = $this->getSelectedQuestion($questionSlug);

        if ($selectedCategory) {
            $questions = $selectedCategory->getQuestions();
        }

        // Throw 404 if there is no category in the database
        if (!$categories) {
            throw $this->createNotFoundException('You need at least 1 active faq category in the database');
        }

        return $this->render(
            '@ChamiloFaq/Faq/index.html.twig',
            [
                'categories' => $categories,
                'questions' => $questions,
                'selectedCategory' => $selectedCategory,
                'selectedQuestion' => $selectedQuestion,
            ]
        );
    }

    /**
     * Index without any collapsing. Will just show all categories and questions at once.
     *
     * @param string $categorySlug
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexWithoutCollapseAction($categorySlug)
    {
        if ($categorySlug) {
            $categories = $this->getCategoryRepository()->retrieveActiveBySlug($categorySlug);
        } else {
            $categories = $this->getCategoryRepository()->retrieveActive();
        }

        if (!$categories) {
            throw $this->createNotFoundException('Faq category not found');
        }

        return $this->render(
            '@ChamiloFaq/Faq/index_without_collapse.html.twig',
            [
                'categories' => $categories,
                'categorySlug' => $categorySlug,
            ]
        );
    }

    /**
     * Open first category or question if none was selected so far.
     *
     * @param string $categorySlug
     * @param string $questionSlug
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function generateRedirectToDefaultSelection($categorySlug, $questionSlug)
    {
        $doRedirect = false;
        //$config = $this->container->getParameter('faq');
        $config = [];
        $config['select_first_category_by_default'] = false;
        $config['select_first_question_by_default'] = false;

        if (!$categorySlug && $config['select_first_category_by_default']) {
            $firstCategory = $this->getCategoryRepository()->retrieveFirst();
            if ($firstCategory instanceof Category) {
                $categorySlug = $firstCategory->getSlug();
                $doRedirect = true;
            } else {
                throw $this->createNotFoundException('Tried to open the first faq category by default, but there was none.');
            }
        }

        if (!$questionSlug && $config['select_first_question_by_default']) {
            $firstQuestion = $this->getQuestionRepository()->retrieveFirstByCategorySlug($categorySlug);
            if ($firstQuestion instanceof Question) {
                $questionSlug = $firstQuestion->getSlug();
                $doRedirect = true;
            } else {
                throw $this->createNotFoundException('Tried to open the first faq question by default, but there was none.');
            }
        }

        if ($doRedirect) {
            return $this->redirect(
                $this->generateUrl('faq', ['categorySlug' => $categorySlug, 'questionSlug' => $questionSlug], true)
            );
        }

        return false;
    }

    /**
     * @param string $questionSlug
     *
     * @return Question
     */
    protected function getSelectedQuestion($questionSlug = null)
    {
        $selectedQuestion = null;

        if ($questionSlug !== null) {
            $selectedQuestion = $this->getQuestionRepository()->getQuestionBySlug($questionSlug);
        }

        return $selectedQuestion;
    }

    /**
     * @param string $categorySlug
     *
     * @return Category
     */
    protected function getSelectedCategory($categorySlug = null)
    {
        $selectedCategory = null;

        if ($categorySlug !== null) {
            $selectedCategory = $this->getCategoryRepository()->getCategoryActiveBySlug($categorySlug);
        }

        return $selectedCategory;
    }

    /**
     * @return QuestionRepository
     */
    protected function getQuestionRepository()
    {
        return $this->container->get('faq.entity.question_repository');
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        return $this->container->get('faq.entity.category_repository');
    }
}
